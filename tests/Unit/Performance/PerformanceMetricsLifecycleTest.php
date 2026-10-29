<?php

declare(strict_types=1);

namespace Tests\Unit\Performance;

use App\Performance\PerformanceMetrics;
use App\Performance\PerformanceMetricsContext;
use App\Performance\PerformanceMetricsLifecycle;
use App\Performance\PerformanceMetricsLogger;
use PHPUnit\Framework\TestCase;

final class PerformanceMetricsLifecycleTest extends TestCase
{
    private string $directory;
    private string $previousErrorLog;
    private string $temporaryErrorLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'alquiler-performance-lifecycle-'
            . bin2hex(random_bytes(6));

        $this->assertTrue(mkdir($this->directory, 0700, true));

        $previousErrorLog = ini_get('error_log');
        $this->previousErrorLog = $previousErrorLog === false
            ? ''
            : $previousErrorLog;

        $this->temporaryErrorLog = $this->directory
            . DIRECTORY_SEPARATOR
            . 'php-error.log';

        ini_set('error_log', $this->temporaryErrorLog);
        PerformanceMetricsContext::clear();
    }

    protected function tearDown(): void
    {
        PerformanceMetricsContext::clear();
        ini_set('error_log', $this->previousErrorLog);

        foreach ([
            'performance.jsonl',
            'performance.jsonl.lock',
            'bloqueo',
            'php-error.log',
        ] as $filename) {
            $path = $this->directory
                . DIRECTORY_SEPARATOR
                . $filename;

            if (is_file($path)) {
                @unlink($path);
            }
        }

        if (is_dir($this->directory)) {
            @rmdir($this->directory);
        }

        parent::tearDown();
    }

    public function test_finish_escribe_el_evento_y_limpia_el_contexto(): void
    {
        $metrics = new PerformanceMetrics(
            requestId: 'request-lifecycle-1',
            method: 'GET',
            route: null,
        );

        PerformanceMetricsContext::activate($metrics);

        $logger = new PerformanceMetricsLogger(
            $this->directory
                . DIRECTORY_SEPARATOR
                . 'performance.jsonl'
        );

        $lifecycle = new PerformanceMetricsLifecycle($metrics, $logger);
        $lifecycle->setRoute('/api/usuarios/{id}');
        $lifecycle->finish(429);

        $path = $this->directory
            . DIRECTORY_SEPARATOR
            . 'performance.jsonl';

        $this->assertFileExists($path);

        $line = file_get_contents($path);
        $this->assertNotFalse($line);

        $event = json_decode(
            trim($line),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame('request-lifecycle-1', $event['request_id']);
        $this->assertSame('/api/usuarios/{id}', $event['route']);
        $this->assertSame(429, $event['status_code']);
        $this->assertTrue($event['is_error']);
        $this->assertNull(PerformanceMetricsContext::current());
    }

    public function test_un_error_fatal_se_registra_como_http_500(): void
    {
        $metrics = new PerformanceMetrics(
            requestId: 'request-lifecycle-fatal',
            method: 'GET',
            route: '/api/propiedades',
        );

        $logger = new PerformanceMetricsLogger(
            $this->directory
                . DIRECTORY_SEPARATOR
                . 'performance.jsonl'
        );

        $lifecycle = new PerformanceMetricsLifecycle($metrics, $logger);
        $lifecycle->finish(200, true);

        $line = file_get_contents(
            $this->directory
                . DIRECTORY_SEPARATOR
                . 'performance.jsonl'
        );

        $this->assertNotFalse($line);

        $event = json_decode(
            trim($line),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(500, $event['status_code']);
        $this->assertTrue($event['is_error']);
    }

    public function test_un_fallo_al_escribir_no_se_propaga_y_limpia_el_contexto(): void
    {
        $blocker = $this->directory
            . DIRECTORY_SEPARATOR
            . 'bloqueo';

        $this->assertNotFalse(file_put_contents($blocker, 'archivo'));

        $metrics = new PerformanceMetrics(
            requestId: 'request-lifecycle-write-error',
            method: 'GET',
            route: '/api/propiedades',
        );

        PerformanceMetricsContext::activate($metrics);

        $logger = new PerformanceMetricsLogger(
            $blocker
                . DIRECTORY_SEPARATOR
                . 'performance.jsonl'
        );

        $lifecycle = new PerformanceMetricsLifecycle($metrics, $logger);

        $lifecycle->finish(200);

        $this->assertNull(PerformanceMetricsContext::current());
        $this->assertFileExists($this->temporaryErrorLog);

        $errorLog = file_get_contents($this->temporaryErrorLog);
        $this->assertNotFalse($errorLog);
        $this->assertStringContainsString(
            'No se pudieron guardar las métricas de rendimiento',
            $errorLog
        );
    }

    public function test_finish_ignora_una_segunda_llamada(): void
    {
        $path = $this->directory
            . DIRECTORY_SEPARATOR
            . 'performance.jsonl';

        $metrics = new PerformanceMetrics(
            requestId: 'request-lifecycle-once',
            method: 'GET',
            route: '/api/health',
        );

        $lifecycle = new PerformanceMetricsLifecycle(
            $metrics,
            new PerformanceMetricsLogger($path)
        );

        $lifecycle->finish(200);
        $lifecycle->finish(500);

        $lines = file($path, FILE_IGNORE_NEW_LINES);

        $this->assertIsArray($lines);
        $this->assertCount(1, $lines);
    }
}