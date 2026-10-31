<?php

declare(strict_types=1);


namespace App\Performance;

use Throwable;

final class PerformanceMetricsLifecycle
{
    private bool $finished = false;

    public function __construct(
        private readonly PerformanceMetrics $metrics,
        private readonly PerformanceMetricsLogger $logger,
    ) {
    }

    public function setRoute(?string $route): void
    {
        $this->metrics->setRoute($route);
    }

    public function registerShutdownHandler(): void
    {
        register_shutdown_function(function (): void {
            $statusCode = http_response_code();
            $lastError = error_get_last();

            $isFatalError = $lastError !== null
                && in_array(
                    $lastError['type'],
                    [
                        E_ERROR,
                        E_PARSE,
                        E_CORE_ERROR,
                        E_COMPILE_ERROR,
                        E_USER_ERROR,
                    ],
                    true
                );

            $this->finish(
                is_int($statusCode) ? $statusCode : 200,
                $isFatalError
            );
        });
    }

    public function finish(int $statusCode, bool $isFatalError = false): void
    {
        if ($this->finished) {
            return;
        }

        $this->finished = true;

        try {
            $event = $this->metrics->finish(
                $isFatalError ? 500 : $statusCode
            );

            $this->logger->write($event);
        } catch (Throwable $exception) {
            error_log(
                'No se pudieron guardar las métricas de rendimiento ('
                . get_class($exception)
                . ').'
            );
        } finally {
            PerformanceMetricsContext::clear();
        }
    }
}