<?php

declare(strict_types=1);

namespace Tests\Unit\Performance;

use App\Performance\PerformanceMetrics;
use App\Performance\PerformanceMetricsContext;
use PHPUnit\Framework\TestCase;

final class PerformanceMetricsContextTest extends TestCase
{
    protected function tearDown(): void
    {
        PerformanceMetricsContext::clear();

        parent::tearDown();
    }

    public function test_no_hay_metricas_activas_por_defecto(): void
    {
        PerformanceMetricsContext::clear();

        $this->assertNull(PerformanceMetricsContext::current());
    }

    public function test_activa_y_devuelve_las_metricas_de_la_peticion_actual(): void
    {
        $metrics = new PerformanceMetrics(
            requestId: 'request-context-1',
            method: 'GET',
            route: '/api/propiedades',
        );

        PerformanceMetricsContext::activate($metrics);

        $this->assertSame(
            $metrics,
            PerformanceMetricsContext::current()
        );
    }

    public function test_clear_quita_las_metricas_activas(): void
    {
        $metrics = new PerformanceMetrics(
            requestId: 'request-context-2',
            method: 'GET',
            route: '/api/usuarios',
        );

        PerformanceMetricsContext::activate($metrics);
        PerformanceMetricsContext::clear();

        $this->assertNull(PerformanceMetricsContext::current());
    }
}