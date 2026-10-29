<?php

declare(strict_types=1);

namespace App\Performance;

use App\Http\Request;
use Throwable;

final class PerformanceMetricsBootstrap
{
    public function __construct(
        private readonly ?PerformanceMetricsLogger $logger = null,
    ) {
    }

    public function start(Request $request): ?PerformanceMetricsLifecycle
    {
        $environment = strtolower(
            trim((string) ($_ENV['APP_ENV'] ?? 'production'))
        );
        $enabled = strtolower(
            trim((string) ($_ENV['PERFORMANCE_METRICS_ENABLED'] ?? 'false'))
        );

        if (
            $environment !== 'development'
            || $enabled !== 'true'
            || !str_starts_with($request->path(), '/api/')
        ) {
            return null;
        }

        try {
            $threshold = filter_var(
                $_ENV['PERFORMANCE_METRICS_SLOW_SQL_MS'] ?? '100',
                FILTER_VALIDATE_INT
            );

            if ($threshold === false || $threshold < 0) {
                throw new \InvalidArgumentException(
                    'PERFORMANCE_METRICS_SLOW_SQL_MS debe ser un entero no negativo.'
                );
            }

            $metrics = new PerformanceMetrics(
                requestId: bin2hex(random_bytes(16)),
                method: $request->method(),
                route: null,
                slowSqlThresholdMs: $threshold,
            );

            PerformanceMetricsContext::activate($metrics);

            $lifecycle = new PerformanceMetricsLifecycle(
                $metrics,
                $this->logger ?? new PerformanceMetricsLogger()
            );
            $lifecycle->registerShutdownHandler();

            return $lifecycle;
        } catch (Throwable $exception) {
            PerformanceMetricsContext::clear();

            error_log(
                'No se pudo iniciar la instrumentación de rendimiento ('
                . get_class($exception)
                . ').'
            );

            return null;
        }
    }
}