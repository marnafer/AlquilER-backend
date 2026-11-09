<?php

declare(strict_types=1);

namespace Tests\Unit\Performance;

use App\Performance\PerformanceMetricsReader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PerformanceMetricsReaderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'alquiler-performance-reader-'
            . bin2hex(random_bytes(6));

        $this->assertTrue(mkdir($this->directory, 0700, true));
    }

    protected function tearDown(): void
    {
        foreach ([
            'performance.jsonl',
            'performance.jsonl.1',
            'performance.jsonl.2',
            'performance.jsonl.3',
            'performance.jsonl.lock',
        ] as $filename) {
            $path = $this->directory
                . DIRECTORY_SEPARATOR
                . $filename;

            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }

        parent::tearDown();
    }

    public function test_lee_el_log_actual_importado_de_produccion_sin_rotaciones(): void
    {
        $path = $this->directory
            . DIRECTORY_SEPARATOR
            . 'performance.jsonl';
        $currentEvent = $this->event('produccion-actual');
        $rotatedEvent = $this->event('produccion-anterior');

        $this->writeEvent($path, $currentEvent);
        $this->writeEvent($path . '.1', $rotatedEvent);

        $events = (new PerformanceMetricsReader())->read(
            'production',
            logPath: $path
        );

        $this->assertCount(1, $events);
        $this->assertSame(
            'produccion-actual',
            $events[0]['timestamp_utc']
        );
        $this->assertSame('GET', $events[0]['method']);
        $this->assertSame('/api/propiedades', $events[0]['route']);
        $this->assertSame(200, $events[0]['status_code']);
        $this->assertSame(12.5, $events[0]['duration_ms']);
        $this->assertSame(2, $events[0]['sql']['query_count']);
    }

    public function test_lee_rotaciones_y_filtra_sesiones_en_desarrollo(): void
    {
        $path = $this->directory
            . DIRECTORY_SEPARATOR
            . 'performance.jsonl';
        $this->writeEvent(
            $path . '.1',
            $this->event('sesion-anterior', 'sesion-anterior')
        );
        $this->writeEvent(
            $path,
            $this->event('sesion-actual', 'sesion-actual')
        );

        $reader = new PerformanceMetricsReader();

        $allEvents = $reader->read('development', logPath: $path);
        $currentSession = $reader->read(
            'development',
            'sesion-actual',
            $path
        );

        $this->assertCount(2, $allEvents);
        $this->assertCount(1, $currentSession);
        $this->assertSame(
            'sesion-actual',
            $currentSession[0]['timestamp_utc']
        );
    }

    public function test_rechaza_entornos_no_soportados(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PerformanceMetricsReader())->read(
            '../production',
            logPath: $this->directory . DIRECTORY_SEPARATOR . 'performance.jsonl'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function event(
        string $timestamp,
        ?string $sessionId = null
    ): array {
        return [
            'timestamp_utc' => $timestamp,
            'method' => 'GET',
            'route' => '/api/propiedades',
            'status_code' => 200,
            'duration_ms' => 12.5,
            'session_id' => $sessionId,
            'sql' => [
                'query_count' => 2,
                'total_ms' => 3.25,
                'slow_query_count' => 0,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $event
     */
    private function writeEvent(string $path, array $event): void
    {
        $contents = json_encode($event, JSON_THROW_ON_ERROR) . PHP_EOL;

        $this->assertNotFalse(file_put_contents($path, $contents));
    }
}
