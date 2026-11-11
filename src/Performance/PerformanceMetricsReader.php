<?php

declare(strict_types=1);

namespace App\Performance;

use JsonException;
use InvalidArgumentException;
use RuntimeException;

class PerformanceMetricsReader
{
    /**
     * @return list<array{
     *     timestamp_utc: string,
     *     method: string,
     *     route: ?string,
     *     status_code: int,
     *     duration_ms: float,
     *     sql: array{query_count: int, total_ms: float, slow_query_count: int}
     * }>
     */
    public function read(
        string $environment,
        ?string $sessionId = null,
        ?string $logPath = null
    ): array {
        if (!in_array($environment, ['development', 'production'], true)) {
            throw new InvalidArgumentException(
                'El entorno de métricas solicitado no es válido.'
            );
        }

        if ($environment === 'production' && $sessionId !== null) {
            throw new InvalidArgumentException(
                'Las métricas importadas de producción no tienen sesiones locales.'
            );
        }

        $logPath ??= PerformanceMetricsLogger::ruta($environment);

        if (!is_dir(dirname($logPath))) {
            return [];
        }

        $lockPath = $logPath . '.lock';
        $lock = $this->openLock($lockPath);

        if ($lock === false) {
            throw new RuntimeException(
                "No se pudo abrir el bloqueo del log de métricas: {$lockPath}"
            );
        }

        try {
            if (!$this->acquireSharedLock($lock)) {
                throw new RuntimeException(
                    "No se pudo bloquear el log de métricas: {$lockPath}"
                );
            }

            $logFiles = $environment === 'development'
                ? [
                    $logPath . '.3',
                    $logPath . '.2',
                    $logPath . '.1',
                    $logPath,
                ]
                : [$logPath];
            $events = [];

            foreach ($logFiles as $filePath) {
                if (!is_file($filePath)) {
                    continue;
                }

                if (!$this->isReadable($filePath)) {
                    throw new RuntimeException(
                        "No se puede leer el log de métricas: {$filePath}"
                    );
                }

                $lines = $this->readLines($filePath);

                if ($lines === false) {
                    throw new RuntimeException(
                        "No se pudo cargar el log de métricas: {$filePath}"
                    );
                }

                foreach ($lines as $index => $line) {
                    if (trim($line) === '') {
                        continue;
                    }

                    try {
                        $event = json_decode(
                            $line,
                            true,
                            512,
                            JSON_THROW_ON_ERROR
                        );
                    } catch (JsonException $exception) {
                        throw new RuntimeException(
                            "JSON inválido en {$filePath}, línea "
                                . ($index + 1) . '.',
                            0,
                            $exception
                        );
                    }

                    if (
                        !is_array($event)
                        || !isset(
                            $event['timestamp_utc'],
                            $event['method'],
                            $event['status_code'],
                            $event['duration_ms'],
                            $event['sql']
                        )
                        || !is_string($event['timestamp_utc'])
                        || !is_string($event['method'])
                        || !is_numeric($event['status_code'])
                        || !is_numeric($event['duration_ms'])
                        || !is_array($event['sql'])
                        || !isset(
                            $event['sql']['query_count'],
                            $event['sql']['total_ms'],
                            $event['sql']['slow_query_count']
                        )
                        || !is_numeric($event['sql']['query_count'])
                        || !is_numeric($event['sql']['total_ms'])
                        || !is_numeric($event['sql']['slow_query_count'])
                        || (
                            isset($event['route'])
                            && $event['route'] !== null
                            && !is_string($event['route'])
                        )
                    ) {
                        throw new RuntimeException(
                            "Estructura de evento inválida en {$filePath}, línea "
                                . ($index + 1) . '.'
                        );
                    }

                    if (
                        !is_finite((float) $event['duration_ms'])
                        || (float) $event['duration_ms'] < 0
                        || !is_finite((float) $event['sql']['total_ms'])
                        || (float) $event['sql']['total_ms'] < 0
                    ) {
                        throw new RuntimeException(
                            "Duración inválida en {$filePath}, línea "
                                . ($index + 1) . '.'
                        );
                    }

                    if (
                        $sessionId !== null
                        && ($event['session_id'] ?? null) !== $sessionId
                    ) {
                        continue;
                    }

                    $events[] = [
                        'timestamp_utc' => $event['timestamp_utc'],
                        'method' => $event['method'],
                        'route' => $event['route'] ?? null,
                        'status_code' => (int) $event['status_code'],
                        'duration_ms' => (float) $event['duration_ms'],
                        'sql' => [
                            'query_count' => (int) $event['sql']['query_count'],
                            'total_ms' => (float) $event['sql']['total_ms'],
                            'slow_query_count' => (int) (
                                $event['sql']['slow_query_count']
                            ),
                        ],
                    ];
                }
            }

            return $events;
        } finally {
            @flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    protected function openLock(string $path): mixed
    {
        return @fopen($path, 'c');
    }

    protected function acquireSharedLock(mixed $handle): bool
    {
        return @flock($handle, LOCK_SH);
    }

    protected function readLines(string $path): array|false
    {
        return file($path, FILE_IGNORE_NEW_LINES);
    }

    protected function isReadable(string $path): bool
    {
        return is_readable($path);
    }
}
