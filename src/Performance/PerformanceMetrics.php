<?php

declare(strict_types=1);

namespace App\Performance;

use Closure;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

final class PerformanceMetrics
{
    private const MAX_SLOW_QUERIES = 10;

    private readonly int $startedAtNanoseconds;

    private int $queryCount = 0;

    private float $sqlDurationMs = 0.0;

    private int $slowQueryCount = 0;

    /**
     * @var list<array{duration_ms: float, fingerprint: ?string}>
     */
    private array $slowQueries = [];

    private bool $finished = false;

    /**
     * @param Closure(): int|null $monotonicClock
     */
    public function __construct(
        private readonly string $requestId,
        private readonly string $method,
        private ?string $route,
        private readonly int $slowSqlThresholdMs = 100,
        private readonly ?Closure $monotonicClock = null,
    ) {
        if ($slowSqlThresholdMs < 0) {
            throw new InvalidArgumentException(
                'El umbral SQL no puede ser negativo.'
            );
        }

        $this->startedAtNanoseconds = $this->now();
    }

    public function recordQuery(float $durationMs, string $sql): void
    {
        if ($this->finished) {
            throw new LogicException(
                'No se pueden registrar consultas después de finalizar la petición.'
            );
        }

        if (!is_finite($durationMs) || $durationMs < 0) {
            throw new InvalidArgumentException(
                'La duración SQL debe ser un número finito no negativo.'
            );
        }

        $this->queryCount++;
        $this->sqlDurationMs += $durationMs;

        if ($durationMs < $this->slowSqlThresholdMs) {
            return;
        }

        $this->slowQueryCount++;

        if (count($this->slowQueries) < self::MAX_SLOW_QUERIES) {
            $this->slowQueries[] = [
                'duration_ms' => round($durationMs, 2),
                'fingerprint' => self::queryFingerprint($sql),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function finish(int $statusCode): array
    {
        if ($this->finished) {
            throw new LogicException(
                'Las métricas de esta petición ya fueron finalizadas.'
            );
        }

        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException(
                'El código HTTP debe estar entre 100 y 599.'
            );
        }

        $elapsedNanoseconds = $this->now() - $this->startedAtNanoseconds;

        if ($elapsedNanoseconds < 0) {
            throw new RuntimeException(
                'El reloj monotónico retrocedió durante la petición.'
            );
        }

        $this->finished = true;

        return [
            'timestamp_utc' => gmdate('Y-m-d\TH:i:s\Z'),
            'request_id' => $this->requestId,
            'method' => $this->method,
            'route' => $this->route,
            'status_code' => $statusCode,
            'duration_ms' => round($elapsedNanoseconds / 1_000_000, 2),
            'is_error' => $statusCode >= 400,
            'sql' => [
                'query_count' => $this->queryCount,
                'total_ms' => round($this->sqlDurationMs, 2),
                'slow_query_count' => $this->slowQueryCount,
                'slow_queries' => $this->slowQueries,
            ],
        ];
    }

    private function now(): int
    {
        $now = $this->monotonicClock !== null
            ? ($this->monotonicClock)()
            : hrtime(true);

        if (!is_int($now)) {
            throw new RuntimeException(
                'No se pudo obtener el tiempo monotónico.'
            );
        }

        return $now;
    }

    private static function queryFingerprint(string $sql): ?string
    {
        if (str_contains($sql, "'") || str_contains($sql, '"')) {
            return null;
        }

        $normalized = preg_replace(
            '/\b\d+(?:\.\d+)?\b/',
            '?',
            $sql
        );

        if ($normalized === null) {
            return null;
        }

        $normalized = strtolower(
            trim(preg_replace('/\s+/', ' ', $normalized) ?? $normalized)
        );

        return hash('sha256', $normalized);
    }

    public function setRoute(?string $route): void
    {
        if ($this->finished) {
            throw new LogicException(
                'No se puede cambiar la ruta después de finalizar la petición.'
            );
        }

        $this->route = $route;
    }
}