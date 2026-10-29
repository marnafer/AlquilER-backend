<?php

declare(strict_types=1);

namespace Tests\Unit\Performance;

use App\Performance\PerformanceMetrics;
use App\Performance\PerformanceMetricsContext;
use App\Performance\PerformanceMetricsQueryListener;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

final class PerformanceMetricsQueryListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        PerformanceMetricsContext::clear();

        parent::tearDown();
    }

    public function test_registra_consultas_ejecutadas_sin_exponer_bindings(): void
    {
        $connection = $this->connectionWithListener();

        $metrics = new PerformanceMetrics(
            requestId: 'request-sql-listener',
            method: 'GET',
            route: '/api/health',
            slowSqlThresholdMs: 0,
        );

        PerformanceMetricsContext::activate($metrics);

        try {
            $connection->select(
                'select ? as value',
                ['binding-privado-de-prueba']
            );
        } finally {
            PerformanceMetricsContext::clear();
        }

        $event = $metrics->finish(200);
        $json = json_encode($event, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $event['sql']['query_count']);
        $this->assertSame(1, $event['sql']['slow_query_count']);
        $this->assertCount(1, $event['sql']['slow_queries']);
        $this->assertStringNotContainsString(
            'binding-privado-de-prueba',
            $json
        );
    }

    public function test_no_atribuye_consultas_ejecutadas_fuera_de_una_peticion(): void
    {
        $connection = $this->connectionWithListener();

        $connection->select('select 1 as value');

        $metrics = new PerformanceMetrics(
            requestId: 'request-sql-isolated',
            method: 'GET',
            route: '/api/health',
        );

        PerformanceMetricsContext::activate($metrics);

        try {
            $connection->select('select 2 as value');
        } finally {
            PerformanceMetricsContext::clear();
        }

        $event = $metrics->finish(200);

        $this->assertSame(1, $event['sql']['query_count']);
    }

    private function connectionWithListener(): \Illuminate\Database\Connection
    {
        $capsule = new Capsule();

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $dispatcher = new Dispatcher(new Container());
        $queryListener = new PerformanceMetricsQueryListener();

        $dispatcher->listen(
            QueryExecuted::class,
            static function (QueryExecuted $event) use ($queryListener): void {
                $queryListener->handle($event);
            }
        );

        $capsule->setEventDispatcher($dispatcher);

        return $capsule->getConnection();
    }
}