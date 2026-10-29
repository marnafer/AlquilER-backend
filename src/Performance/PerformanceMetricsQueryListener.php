<?php

declare(strict_types=1);

namespace App\Performance;

use Illuminate\Database\Events\QueryExecuted;
use Throwable;

final class PerformanceMetricsQueryListener
{
    public function handle(QueryExecuted $event): void
    {
        $metrics = PerformanceMetricsContext::current();

        if ($metrics === null) {
            return;
        }

        try {
            // No se accede ni se registra el array de bindings.
            $metrics->recordQuery($event->time, $event->sql);
        } catch (Throwable $exception) {
            error_log(
                'No se pudieron registrar métricas SQL ('
                . get_class($exception)
                . ').'
            );
        }
    }
}