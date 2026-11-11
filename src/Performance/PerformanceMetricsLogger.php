<?php

declare(strict_types=1);

namespace App\Performance;

use JsonException;
use LengthException;
use RuntimeException;

class PerformanceMetricsLogger
{
    private const DEFAULT_MAX_BYTES = 5 * 1024 * 1024;
    private const DEFAULT_MAX_BACKUPS = 3;

    private readonly string $path;

    public function __construct(
        ?string $path = null,
        private readonly int $maxBytes = self::DEFAULT_MAX_BYTES,
        private readonly int $maxBackups = self::DEFAULT_MAX_BACKUPS,
    ) {
        if ($maxBytes < 1) {
            throw new RuntimeException(
                'El tamaño máximo del log debe ser positivo.'
            );
        }

        if ($maxBackups < 1) {
            throw new RuntimeException(
                'Debe conservarse al menos una generación de respaldo.'
            );
        }

        $this->path = $path ?? self::ruta();
    }

    public static function ruta(?string $environment = null): string
    {
        $environment ??= $_ENV['APP_ENV'] ?? 'production';
        $environment = strtolower(trim($environment));

        if (!preg_match('/\A[a-z0-9_-]+\z/', $environment)) {
            throw new RuntimeException(
                'APP_ENV debe contener solo letras, números, guiones o guiones bajos.'
            );
        }

        return dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'logs'
            . DIRECTORY_SEPARATOR
            . $environment
            . DIRECTORY_SEPARATOR
            . 'performance.jsonl';
    }

    /**
     * @param array<string, mixed> $event
     *
     * @throws JsonException
     */
    public function write(array $event): void
    {
        $line = json_encode(
            $event,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_THROW_ON_ERROR
        ) . PHP_EOL;

        if (strlen($line) > $this->maxBytes) {
            throw new LengthException(
                'El evento supera el tamaño máximo configurado para el log.'
            );
        }

        $directory = dirname($this->path);

        if (
            !is_dir($directory)
            && !$this->makeDirectory($directory, 0700, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                "No se pudo crear el directorio de métricas: {$directory}"
            );
        }

        $lockPath = $this->path . '.lock';
        $lockHandle = $this->openLock($lockPath);

        if ($lockHandle === false) {
            throw new RuntimeException(
                "No se pudo abrir el archivo de bloqueo: {$lockPath}"
            );
        }

        $locked = false;

        try {
            if (!$this->acquireLock($lockHandle)) {
                throw new RuntimeException(
                    "No se pudo bloquear el archivo de métricas: {$lockPath}"
                );
            }

            $locked = true;
            $this->rotateIfNeeded(strlen($line));
            $this->appendLine($line);
        } finally {
            if ($locked) {
                flock($lockHandle, LOCK_UN);
            }

            fclose($lockHandle);
        }
    }

    private function rotateIfNeeded(int $incomingBytes): void
    {
        if (!is_file($this->path)) {
            return;
        }

        clearstatcache(true, $this->path);
        $currentBytes = $this->fileSize($this->path);

        if ($currentBytes === false) {
            throw new RuntimeException(
                "No se pudo consultar el tamaño del log: {$this->path}"
            );
        }

        if ($currentBytes + $incomingBytes <= $this->maxBytes) {
            return;
        }

        for ($generation = $this->maxBackups; $generation >= 1; $generation--) {
            $source = $generation === 1
                ? $this->path
                : $this->path . '.' . ($generation - 1);
            $destination = $this->path . '.' . $generation;

            if (!is_file($source)) {
                continue;
            }

            if (
                is_file($destination)
                && !$this->deleteFile($destination)
            ) {
                throw new RuntimeException(
                    "No se pudo eliminar el log rotado: {$destination}"
                );
            }

            if (!$this->renameFile($source, $destination)) {
                throw new RuntimeException(
                    "No se pudo rotar el log {$source} a {$destination}"
                );
            }
        }
    }

    private function appendLine(string $line): void
    {
        $handle = $this->openLog($this->path);

        if ($handle === false) {
            throw new RuntimeException(
                "No se pudo abrir el log de métricas: {$this->path}"
            );
        }

        try {
            if ($this->seekLog($handle, 0, SEEK_END) !== 0) {
                throw new RuntimeException(
                    "No se pudo posicionar el log: {$this->path}"
                );
            }

            $originalBytes = $this->tellLog($handle);

            if ($originalBytes === false) {
                throw new RuntimeException(
                    "No se pudo obtener la posición del log: {$this->path}"
                );
            }

            $offset = 0;
            $lineLength = strlen($line);

            while ($offset < $lineLength) {
                $written = $this->writeLog($handle, substr($line, $offset));

                if ($written === false || $written === 0) {
                    $this->rollback($handle, $originalBytes);

                    throw new RuntimeException(
                        "No se pudo escribir el evento en: {$this->path}"
                    );
                }

                $offset += $written;
            }

            if (!$this->flushLog($handle)) {
                $this->rollback($handle, $originalBytes);

                throw new RuntimeException(
                    "No se pudo vaciar el búfer del log: {$this->path}"
                );
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     */
    private function rollback($handle, int $originalBytes): void
    {
        if (
            !$this->truncateLog($handle, $originalBytes)
            || !$this->flushLog($handle)
        ) {
            throw new RuntimeException(
                "No se pudo revertir una escritura incompleta en: {$this->path}"
            );
        }
    }

    protected function makeDirectory(string $directory, int $mode, bool $recursive): bool
    {
        return @mkdir($directory, $mode, $recursive);
    }

    protected function openLock(string $path): mixed
    {
        return @fopen($path, 'c');
    }

    protected function acquireLock(mixed $handle): bool
    {
        return @flock($handle, LOCK_EX);
    }

    protected function fileSize(string $path): int|false
    {
        clearstatcache(true, $path);

        return @filesize($path);
    }

    protected function deleteFile(string $path): bool
    {
        return @unlink($path);
    }

    protected function renameFile(string $source, string $destination): bool
    {
        return @rename($source, $destination);
    }

    protected function openLog(string $path): mixed
    {
        return @fopen($path, 'c+b');
    }

    protected function seekLog(mixed $handle, int $offset, int $whence): int
    {
        return fseek($handle, $offset, $whence);
    }

    protected function tellLog(mixed $handle): int|false
    {
        return ftell($handle);
    }

    protected function writeLog(mixed $handle, string $data): int|false
    {
        return @fwrite($handle, $data);
    }

    protected function flushLog(mixed $handle): bool
    {
        return @fflush($handle);
    }

    protected function truncateLog(mixed $handle, int $size): bool
    {
        return @ftruncate($handle, $size);
    }
}