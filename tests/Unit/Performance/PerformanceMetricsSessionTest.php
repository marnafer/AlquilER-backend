<?php

declare(strict_types=1);

namespace Tests\Unit\Performance;

use App\Performance\PerformanceMetricsSession;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PerformanceMetricsSessionTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'alquiler-performance-session-'
            . bin2hex(random_bytes(6));

        $this->assertTrue(mkdir($this->directory, 0700, true));
    }

    protected function tearDown(): void
    {
        foreach (['sessions.json', 'sessions.json.lock'] as $filename) {
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

    public function test_separa_el_estado_de_capturas_por_entorno(): void
    {
        $developmentDirectory = PerformanceMetricsSession::directorio(
            'development'
        );
        $productionDirectory = PerformanceMetricsSession::directorio(
            'production'
        );

        $this->assertNotSame($developmentDirectory, $productionDirectory);
        $this->assertStringEndsWith(
            DIRECTORY_SEPARATOR . 'development',
            $developmentDirectory
        );
        $this->assertStringEndsWith(
            DIRECTORY_SEPARATOR . 'production',
            $productionDirectory
        );
    }

    public function test_inicia_detiene_y_conserva_el_historial_de_capturas(): void
    {
        $session = new PerformanceMetricsSession($this->directory);

        $this->assertNull($session->activeSessionId());

        $firstId = $session->start();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $firstId);
        $this->assertSame($firstId, $session->activeSessionId());
        $this->assertSame($firstId, $session->status()['last_session_id']);
        $this->assertNull($session->sessions()[0]['stopped_at']);
        $this->assertSame($firstId, $session->stop());
        $this->assertNull($session->activeSessionId());
        $this->assertNotNull($session->sessions()[0]['stopped_at']);

        $secondId = $session->start();

        $this->assertNotSame($firstId, $secondId);
        $this->assertCount(2, $session->sessions());
    }

    public function test_no_permite_iniciar_dos_capturas_simultaneas(): void
    {
        $session = new PerformanceMetricsSession($this->directory);
        $session->start();

        $this->expectException(RuntimeException::class);
        $session->start();
    }

    public function test_rechaza_entorno_invalido_en_directorio(): void
    {
        $this->expectException(RuntimeException::class);
        PerformanceMetricsSession::directorio('../production');
    }

    public function test_stop_devuelve_null_si_no_hay_captura_activa(): void
    {
        $session = new PerformanceMetricsSession($this->directory);

        $this->assertNull($session->stop());
    }

    public function test_falla_si_no_puede_crear_directorio(): void
    {
        $blocker = $this->directory . DIRECTORY_SEPARATOR . 'bloqueo';
        file_put_contents($blocker, 'archivo');

        $session = new PerformanceMetricsSession(
            $blocker . DIRECTORY_SEPARATOR . 'sub'
        );

        $this->expectException(RuntimeException::class);
        $session->status();

        @unlink($blocker);
    }

    public function test_falla_si_no_puede_abrir_bloqueo(): void
    {
        $lockPath = $this->directory . DIRECTORY_SEPARATOR . 'sessions.json.lock';
        mkdir($lockPath);

        try {
            $session = new PerformanceMetricsSession($this->directory);
            $this->expectException(RuntimeException::class);
            $session->status();
        } finally {
            @rmdir($lockPath);
        }
    }

    public function test_falla_si_no_puede_bloquear_estado(): void
    {
        $session = new class ($this->directory) extends PerformanceMetricsSession {
            protected function acquireLock(mixed $handle): bool
            {
                return false;
            }
        };

        $this->expectException(RuntimeException::class);
        $session->status();
    }

    public function test_falla_si_no_puede_leer_estado(): void
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . 'sessions.json';
        file_put_contents($path, '{}');

        $session = new class ($this->directory) extends PerformanceMetricsSession {
            protected function readStateContents(string $path): string|false
            {
                return false;
            }
        };

        $this->expectException(RuntimeException::class);
        $session->status();
    }

    public function test_falla_si_estado_es_invalido(): void
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . 'sessions.json';
        file_put_contents($path, json_encode(['foo' => 'bar']));

        $session = new PerformanceMetricsSession($this->directory);

        $this->expectException(RuntimeException::class);
        $session->status();
    }

    public function test_falla_si_no_puede_guardar_estado(): void
    {
        $directory = $this->directory . DIRECTORY_SEPARATOR . 'sessions.json';
        mkdir($directory);

        $session = new PerformanceMetricsSession($this->directory);

        $this->expectException(RuntimeException::class);
        $session->start();
    }
}
