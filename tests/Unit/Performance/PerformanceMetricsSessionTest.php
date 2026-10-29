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
}
