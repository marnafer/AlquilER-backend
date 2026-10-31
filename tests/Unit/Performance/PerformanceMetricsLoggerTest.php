<?php

declare(strict_types=1);

namespace Tests\Unit\Performance;

use App\Performance\PerformanceMetricsLogger;
use LengthException;
use RuntimeException;
use Tests\TestCase;

final class PerformanceMetricsLoggerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'alquiler-performance-'
            . bin2hex(random_bytes(6));

        $created = mkdir($this->directory, 0700, true);

        $this->assertTrue(
            $created,
            'No se pudo crear el directorio temporal de pruebas.'
        );
    }

    protected function tearDown(): void
    {
        foreach ([
            'performance.jsonl',
            'performance.jsonl.1',
            'performance.jsonl.2',
            'performance.jsonl.3',
            'performance.jsonl.lock',
            'bloqueo',
        ] as $filename) {
            $path = $this->directory . DIRECTORY_SEPARATOR . $filename;

            if (is_file($path)) {
                @unlink($path);
            }
        }

        if (is_dir($this->directory)) {
            @rmdir($this->directory);
        }

        parent::tearDown();
    }

    public function test_escribe_eventos_json_validos_una_linea_por_evento(): void
    {
        $path = $this->directory
            . DIRECTORY_SEPARATOR
            . 'performance.jsonl';
        $logger = new PerformanceMetricsLogger($path);

        $logger->write([
            'request_id' => 'request-1',
            'status_code' => 200,
        ]);
        $logger->write([
            'request_id' => 'request-2',
            'status_code' => 500,
        ]);

        $lines = file($path, FILE_IGNORE_NEW_LINES);

        $this->assertIsArray($lines);
        $this->assertCount(2, $lines);
        $this->assertSame(
            ['request_id' => 'request-1', 'status_code' => 200],
            json_decode($lines[0], true, 512, JSON_THROW_ON_ERROR)
        );
        $this->assertSame(
            ['request_id' => 'request-2', 'status_code' => 500],
            json_decode($lines[1], true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function test_rota_el_log_y_conserva_solo_las_generaciones_configuradas(): void
    {
        $path = $this->directory
            . DIRECTORY_SEPARATOR
            . 'performance.jsonl';
        $logger = new PerformanceMetricsLogger(
            path: $path,
            maxBytes: 10,
            maxBackups: 3,
        );

        for ($number = 1; $number <= 5; $number++) {
            $logger->write(['n' => $number]);
        }

        $this->assertSame(5, $this->readNumber($path));
        $this->assertSame(4, $this->readNumber($path . '.1'));
        $this->assertSame(3, $this->readNumber($path . '.2'));
        $this->assertSame(2, $this->readNumber($path . '.3'));
        $this->assertFileDoesNotExist($path . '.4');
    }

    public function test_rechaza_un_evento_mayor_que_el_tamano_maximo(): void
    {
        $path = $this->directory
            . DIRECTORY_SEPARATOR
            . 'performance.jsonl';
        $logger = new PerformanceMetricsLogger($path, maxBytes: 5);

        $this->expectException(LengthException::class);

        $logger->write(['value' => 'evento demasiado grande']);
    }

    public function test_informa_si_no_puede_crear_el_directorio_del_log(): void
    {
        $blocker = $this->directory
            . DIRECTORY_SEPARATOR
            . 'bloqueo';

        $this->assertNotFalse(file_put_contents($blocker, 'archivo'));

        $path = $blocker
            . DIRECTORY_SEPARATOR
            . 'performance.jsonl';
        $logger = new PerformanceMetricsLogger($path);

        $this->expectException(RuntimeException::class);

        $logger->write(['request_id' => 'request-error']);
    }

    private function readNumber(string $path): int
    {
        $contents = file_get_contents($path);

        $this->assertNotFalse($contents);

        $event = json_decode(
            trim($contents),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertIsArray($event);

        return $event['n'];
    }
}