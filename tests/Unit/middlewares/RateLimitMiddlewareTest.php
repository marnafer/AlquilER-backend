<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use App\Middlewares\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;

final class RateLimitMiddlewareTest extends TestCase
{
    private string $directorio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directorio =
            sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            'alquiler-backend-rate-limit-test-' .
            bin2hex(
                random_bytes(8)
            );

        mkdir(
            $this->directorio,
            0777,
            true
        );
    }

    protected function tearDown(): void
    {
        $this->eliminarDirectorio(
            $this->directorio
        );

        parent::tearDown();
    }

    public function test_permite_la_primera_solicitud(): void
    {
        $resultado =
            RateLimitMiddleware::evaluar(
                '203.0.113.10',
                1000,
                $this->directorio
            );

        $this->assertTrue(
            $resultado['permitido']
        );

        $this->assertSame(
            119,
            $resultado['restantes']
        );

        $this->assertSame(
            0,
            $resultado['retry_after']
        );
    }

    public function test_permite_hasta_120_solicitudes(): void
    {
        $resultado = null;

        for ($i = 1; $i <= 120; $i++) {
            $resultado =
                RateLimitMiddleware::evaluar(
                    '203.0.113.20',
                    1000,
                    $this->directorio
                );
        }

        $this->assertNotNull(
            $resultado
        );

        $this->assertTrue(
            $resultado['permitido']
        );

        $this->assertSame(
            0,
            $resultado['restantes']
        );
    }

    public function test_rechaza_la_solicitud_121(): void
    {
        for ($i = 1; $i <= 120; $i++) {
            RateLimitMiddleware::evaluar(
                '203.0.113.30',
                1000,
                $this->directorio
            );
        }

        $resultado =
            RateLimitMiddleware::evaluar(
                '203.0.113.30',
                1000,
                $this->directorio
            );

        $this->assertFalse(
            $resultado['permitido']
        );

        $this->assertSame(
            0,
            $resultado['restantes']
        );

        $this->assertSame(
            60,
            $resultado['retry_after']
        );
    }

    public function test_reinicia_el_limite_al_comenzar_una_nueva_ventana(): void
    {
        for ($i = 1; $i <= 120; $i++) {
            RateLimitMiddleware::evaluar(
                '203.0.113.40',
                1000,
                $this->directorio
            );
        }

        $resultado =
            RateLimitMiddleware::evaluar(
                '203.0.113.40',
                1060,
                $this->directorio
            );

        $this->assertTrue(
            $resultado['permitido']
        );

        $this->assertSame(
            119,
            $resultado['restantes']
        );

        $this->assertSame(
            0,
            $resultado['retry_after']
        );
    }

    public function test_el_limite_es_independiente_por_ip(): void
    {
        for ($i = 1; $i <= 120; $i++) {
            RateLimitMiddleware::evaluar(
                '203.0.113.50',
                1000,
                $this->directorio
            );
        }

        $resultado =
            RateLimitMiddleware::evaluar(
                '203.0.113.51',
                1000,
                $this->directorio
            );

        $this->assertTrue(
            $resultado['permitido']
        );

        $this->assertSame(
            119,
            $resultado['restantes']
        );
    }

    private function eliminarDirectorio(
        string $directorio
    ): void {
        if (!is_dir($directorio)) {
            return;
        }

        $archivos =
            scandir($directorio);

        if ($archivos === false) {
            return;
        }

        foreach ($archivos as $archivo) {
            if (
                $archivo === '.' ||
                $archivo === '..'
            ) {
                continue;
            }

            $ruta =
                $directorio .
                DIRECTORY_SEPARATOR .
                $archivo;

            if (is_dir($ruta)) {
                $this->eliminarDirectorio(
                    $ruta
                );

                continue;
            }

            @unlink($ruta);
        }

        @rmdir($directorio);
    }
}