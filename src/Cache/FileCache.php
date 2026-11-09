<?php

declare(strict_types=1);

namespace App\Cache;

use JsonException;
use RuntimeException;
use App\Performance\PerformanceMetricsContext;

final class FileCache
{
    public function __construct(
        private readonly string $directory
            = __DIR__ . '/../../storage/cache'
    ) {
    }

    /**
     * @param callable(): array<string, mixed> $loader
     * @return array<string, mixed>
     */
    public function remember(
        string $key,
        int $ttlSeconds,
        callable $loader
    ): array {
        if ($ttlSeconds < 1) {
            throw new \InvalidArgumentException(
                'El TTL de la caché debe ser positivo.'
            );
        }

        return $this->withLock($key, function () use (
            $key,
            $ttlSeconds,
            $loader
        ): array {
            $path = $this->pathFor($key);

            if (is_file($path)) {
                $entry = $this->read($path);

                if ($entry['expires_at'] > time()) {

                $metrics = PerformanceMetricsContext::current();

                    if ($metrics !== null) {
                        $metrics->recordCacheHit();
                    }
                    return $entry['value'];
                }

                if (!@unlink($path) && is_file($path)) {
                    throw new RuntimeException(
                        "No se pudo eliminar una entrada de caché expirada: {$path}"
                    );
                }
            }

            $metrics = PerformanceMetricsContext::current();

            if ($metrics === null) {
                $value = $loader();
            } else {
                $loaderStartedAt = hrtime(true);

                try {
                    $value = $loader();
                } finally {
                    $metrics->recordCacheMiss(
                        (hrtime(true) - $loaderStartedAt) / 1_000_000
                    );
                }
            }
            $this->write($path, [
                'expires_at' => time() + $ttlSeconds,
                'value' => $value,
            ]);

            return $value;
        });
    }

    public function forget(string $key): void
    {
        $this->withLock($key, function () use ($key): void {
            $path = $this->pathFor($key);

            if (is_file($path) && !@unlink($path) && is_file($path)) {
                throw new RuntimeException(
                    "No se pudo invalidar la entrada de caché: {$path}"
                );
            }
        });
    }

    private function pathFor(string $key): string
    {
        if (trim($key) === '') {
            throw new \InvalidArgumentException(
                'La clave de caché no puede estar vacía.'
            );
        }

        return rtrim($this->directory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . hash('sha256', $key)
            . '.json';
    }

    /**
     * @return array{expires_at: int, value: array<string, mixed>}
     */
    private function read(string $path): array
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(
                "No se pudo leer una entrada de caché: {$path}"
            );
        }

        try {
            $entry = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                "La entrada de caché contiene JSON inválido: {$path}",
                previous: $exception
            );
        }

        if (
            !is_array($entry)
            || !isset($entry['expires_at'], $entry['value'])
            || !is_int($entry['expires_at'])
            || !is_array($entry['value'])
        ) {
            throw new RuntimeException(
                "La estructura de la entrada de caché es inválida: {$path}"
            );
        }

        return $entry;
    }

    /**
     * @param array{expires_at: int, value: array<string, mixed>} $entry
     */
    private function write(string $path, array $entry): void
    {
        try {
            $contents = json_encode(
                $entry,
                JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'No se pudo codificar el valor de caché.',
                previous: $exception
            );
        }

        $temporaryPath = tempnam($this->directory, 'cache-');

        if ($temporaryPath === false) {
            throw new RuntimeException(
                "No se pudo crear un archivo temporal en: {$this->directory}"
            );
        }

        try {
            if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false) {
                throw new RuntimeException(
                    "No se pudo escribir el archivo temporal de caché: {$temporaryPath}"
                );
            }

            @chmod($temporaryPath, 0600);

            if (!@rename($temporaryPath, $path)) {
                throw new RuntimeException(
                    "No se pudo publicar la entrada de caché: {$path}"
                );
            }
        } finally {
            if (is_file($temporaryPath) && !@unlink($temporaryPath)) {
                throw new RuntimeException(
                    "No se pudo limpiar el archivo temporal de caché: {$temporaryPath}"
                );
            }
        }
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function withLock(string $key, callable $callback): mixed
    {
        if (
            !is_dir($this->directory)
            && !@mkdir($this->directory, 0700, true)
            && !is_dir($this->directory)
        ) {
            throw new RuntimeException(
                "No se pudo crear el directorio de caché: {$this->directory}"
            );
        }

        $lockPath = $this->pathFor($key) . '.lock';
        $lock = @fopen($lockPath, 'c');

        if ($lock === false) {
            throw new RuntimeException(
                "No se pudo abrir el bloqueo de caché: {$lockPath}"
            );
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException(
                    "No se pudo bloquear la entrada de caché: {$lockPath}"
                );
            }

            @chmod($lockPath, 0600);

            return $callback();
        } finally {
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }
}
