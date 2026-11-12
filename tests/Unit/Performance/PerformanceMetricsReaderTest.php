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
    public function test_rechaza_sesion_en_produccion(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new PerformanceMetricsReader())->read(
            'production',
            'sesion',
            $this->directory . DIRECTORY_SEPARATOR . 'performance.jsonl'
        );
    }

    public function test_devuelve_vacio_si_no_existe_directorio(): void
    {
        $missingDirectory = $this->directory . DIRECTORY_SEPARATOR . 'inexistente';
        $path = $missingDirectory . DIRECTORY_SEPARATOR . 'performance.jsonl';

        $events = (new PerformanceMetricsReader())->read(
            'production',
            logPath: $path
        );

        $this->assertSame([], $events);
    }

    public function test_falla_si_no_puede_abrir_bloqueo(): void
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . 'performance.jsonl';
        $lockPath = $path . '.lock';
        mkdir($lockPath);

        try {
            $this->expectException(\RuntimeException::class);
            (new PerformanceMetricsReader())->read('production', logPath: $path);
        } finally {
            @rmdir($lockPath);
        }
    }

    public function test_falla_si_no_puede_bloquear_log(): void
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . 'performance.jsonl';
        file_put_contents($path, json_encode($this->event('x')) . PHP_EOL);

        $reader = new class ($path) extends PerformanceMetricsReader {
            protected function acquireSharedLock(mixed $handle): bool
            {
                return false;
            }
        };

        $this->expectException(\RuntimeException::class);
        $reader->read('production', logPath: $path);
    }

    public function test_ignora_lineas_vacias(): void
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . 'performance.jsonl';
        file_put_contents($path, PHP_EOL . json_encode($this->event('x')) . PHP_EOL);

        $events = (new PerformanceMetricsReader())->read('production', logPath: $path);

        $this->assertCount(1, $events);
    }

    public function test_falla_si_json_es_invalido(): void
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . 'performance.jsonl';
        file_put_contents($path, "{invalido" . PHP_EOL);

        $this->expectException(\RuntimeException::class);
        (new PerformanceMetricsReader())->read('production', logPath: $path);
    }

    public function test_falla_si_estructura_es_invalida(): void
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . 'performance.jsonl';
        file_put_contents($path, json_encode(['foo' => 'bar']) . PHP_EOL);

        $this->expectException(\RuntimeException::class);
        (new PerformanceMetricsReader())->read('production', logPath: $path);
    }

    public function test_falla_si_duracion_es_invalida(): void
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . 'performance.jsonl';
        $event = $this->event('x');
        $event['duration_ms'] = -1;
        $event['sql']['total_ms'] = 1;
        file_put_contents($path, json_encode($event) . PHP_EOL);

        $this->expectException(\RuntimeException::class);
        (new PerformanceMetricsReader())->read('production', logPath: $path);
    }

    public function test_falla_si_no_se_puede_leer_log(): void
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . 'performance.jsonl';
        file_put_contents($path, json_encode($this->event('x')) . PHP_EOL);

        $reader = new class ($path) extends PerformanceMetricsReader {
            protected function isReadable(string $path): bool
            {
                return false;
            }
        };

        $this->expectException(\RuntimeException::class);
        $reader->read('production', logPath: $path);
    }

    public function test_falla_si_no_puede_cargar_log(): void
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . 'performance.jsonl';
        file_put_contents($path, json_encode($this->event('x')) . PHP_EOL);

        $reader = new class ($path) extends PerformanceMetricsReader {
            protected function readLines(string $path): array|false
            {
                return false;
            }
        };

        $this->expectException(\RuntimeException::class);
        $reader->read('production', logPath: $path);
    }

    private function writeEvent(string $path, array $event): void
    {
        $contents = json_encode($event, JSON_THROW_ON_ERROR) . PHP_EOL;

        $this->assertNotFalse(file_put_contents($path, $contents));
    }
}
