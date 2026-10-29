<?php

declare(strict_types=1);


namespace Tests\Unit\Performance;

use App\Performance\PerformanceMetrics;
use PHPUnit\Framework\TestCase;

final class PerformanceMetricsTest extends TestCase
{
    public function test_finish_mide_la_duracion_con_un_reloj_monotonico(): void
    {
        $times = [1_000_000_000, 1_123_456_789];
        $clockReads = 0;

        $metrics = new PerformanceMetrics(
            requestId: 'request-1',
            method: 'GET',
            route: '/api/propiedades',
            sessionId: 'capture-1',
            monotonicClock: static function () use (&$clockReads, $times): int {
                return $times[$clockReads++];
            },
        );

        $event = $metrics->finish(200);

        $this->assertSame(123.46, $event['duration_ms']);
        $this->assertSame('request-1', $event['request_id']);
        $this->assertSame('capture-1', $event['session_id']);
        $this->assertSame('GET', $event['method']);
        $this->assertSame('/api/propiedades', $event['route']);
        $this->assertSame(200, $event['status_code']);
        $this->assertFalse($event['is_error']);
    }

    public function test_acumula_consultas_y_detecta_las_que_superan_el_umbral(): void
    {
        $metrics = new PerformanceMetrics(
            requestId: 'request-2',
            method: 'GET',
            route: '/api/propiedades',
            slowSqlThresholdMs: 100,
        );

        $metrics->recordQuery(10.126, 'select * from propiedades');
        $metrics->recordQuery(100.555, 'select * from propiedades where id = 42');

        $event = $metrics->finish(200);

        $this->assertSame(2, $event['sql']['query_count']);
        $this->assertSame(110.68, $event['sql']['total_ms']);
        $this->assertSame(1, $event['sql']['slow_query_count']);
        $this->assertCount(1, $event['sql']['slow_queries']);
        $this->assertSame(100.56, $event['sql']['slow_queries'][0]['duration_ms']);
    }

    public function test_no_incluye_consultas_con_literales_entre_comillas(): void
    {
        $metrics = new PerformanceMetrics(
            requestId: 'request-3',
            method: 'POST',
            route: '/api/usuarios',
        );

        $metrics->recordQuery(
            150,
            "select * from usuarios where email = 'persona@example.com'"
        );

        $event = $metrics->finish(500);
        $json = json_encode($event, JSON_THROW_ON_ERROR);

        $this->assertTrue($event['is_error']);
        $this->assertSame(500, $event['status_code']);
        $this->assertNull($event['sql']['slow_queries'][0]['fingerprint']);
        $this->assertStringNotContainsString('persona@example.com', $json);
    }

    public function test_las_metricas_de_dos_peticiones_no_se_mezclan(): void
    {
        $first = new PerformanceMetrics('request-a', 'GET', '/api/propiedades');
        $second = new PerformanceMetrics('request-b', 'GET', '/api/usuarios');

        $first->recordQuery(12, 'select * from propiedades');
        $second->recordQuery(25, 'select * from usuarios');

        $firstEvent = $first->finish(200);
        $secondEvent = $second->finish(404);

        $this->assertSame(1, $firstEvent['sql']['query_count']);
        $this->assertSame(12.0, $firstEvent['sql']['total_ms']);
        $this->assertSame(1, $secondEvent['sql']['query_count']);
        $this->assertSame(25.0, $secondEvent['sql']['total_ms']);
        $this->assertFalse($firstEvent['is_error']);
        $this->assertTrue($secondEvent['is_error']);
    }

    public function test_permite_asignar_la_plantilla_de_ruta_despues_de_crear_las_metricas(): void
    {
        $metrics = new PerformanceMetrics(
            requestId: 'request-route-1',
            method: 'GET',
            route: null,
        );

        $metrics->setRoute('/api/usuarios/{id}');

        $event = $metrics->finish(200);

        $this->assertSame('/api/usuarios/{id}', $event['route']);
    }
}