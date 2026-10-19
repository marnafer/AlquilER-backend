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

    private function consumirSolicitudes(
        string $metodo,
        string $ruta,
        string $ip,
        int $cantidad,
        int $ahora = 1000
    ): array {
        $resultado = [];

        for ($i = 0; $i < $cantidad; $i++) {
            $resultado = RateLimitMiddleware::evaluarSolicitud(
                $metodo,
                $ruta,
                $ip,
                $ahora,
                $this->directorio
            );
        }

        return $resultado;
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

    
    public function test_no_reinicia_el_limite_antes_de_finalizar_la_ventana(): void
    {
        for ($i = 1; $i <= 120; $i++) {
            RateLimitMiddleware::evaluar(
                '203.0.113.60',
                1000,
                $this->directorio
            );
        }

        $resultado = RateLimitMiddleware::evaluar(
            '203.0.113.60',
            1059,
            $this->directorio
        );

        $this->assertFalse($resultado['permitido']);
        $this->assertSame(0, $resultado['restantes']);
        $this->assertSame(1, $resultado['retry_after']);
    }

    public function test_reinicia_la_ventana_si_el_reloj_retrocede(): void
    {
        for ($i = 1; $i <= 120; $i++) {
            RateLimitMiddleware::evaluar(
                '203.0.113.70',
                1000,
                $this->directorio
            );
        }

        $resultado = RateLimitMiddleware::evaluar(
            '203.0.113.70',
            999,
            $this->directorio
        );

        $this->assertTrue($resultado['permitido']);
        $this->assertSame(119, $resultado['restantes']);
        $this->assertSame(0, $resultado['retry_after']);
    }

    
public function test_login_y_get_tienen_contadores_independientes(): void
{
    $this->consumirSolicitudes(
        'POST',
        '/api/autenticador/login',
        '203.0.113.85',
        5
    );

    $login = RateLimitMiddleware::evaluarSolicitud(
        'POST',
        '/api/autenticador/login',
        '203.0.113.85',
        1000,
        $this->directorio
    );

    $get = RateLimitMiddleware::evaluarSolicitud(
        'GET',
        '/api/propiedades',
        '203.0.113.85',
        1000,
        $this->directorio
    );

    $this->assertFalse($login['permitido']);
    $this->assertTrue($get['permitido']);
    $this->assertSame(119, $get['restantes']);
}

public function test_get_aplica_limite_de_ciento_veinte(): void
{
    $this->consumirSolicitudes(
        'GET',
        '/api/propiedades',
        '203.0.113.86',
        120
    );

    $resultado = RateLimitMiddleware::evaluarSolicitud(
        'GET',
        '/api/propiedades',
        '203.0.113.86',
        1000,
        $this->directorio
    );

    $this->assertSame('get', $resultado['nombre']);
    $this->assertSame(120, $resultado['limite']);
    $this->assertFalse($resultado['permitido']);
    $this->assertSame(60, $resultado['retry_after']);
}

public function test_escrituras_generales_aplican_limite_de_treinta(): void
{
    $this->consumirSolicitudes(
        'POST',
        '/api/favoritos',
        '203.0.113.87',
        30
    );

    $resultado = RateLimitMiddleware::evaluarSolicitud(
        'POST',
        '/api/favoritos',
        '203.0.113.87',
        1000,
        $this->directorio
    );

    $this->assertSame('write', $resultado['nombre']);
    $this->assertSame(30, $resultado['limite']);
    $this->assertFalse($resultado['permitido']);
    $this->assertSame(60, $resultado['retry_after']);
}

public function test_contacto_aplica_limite_de_diez(): void
{
    $this->consumirSolicitudes(
        'POST',
        '/api/contacto',
        '203.0.113.88',
        10
    );

    $resultado = RateLimitMiddleware::evaluarSolicitud(
        'POST',
        '/api/contacto',
        '203.0.113.88',
        1000,
        $this->directorio
    );

    $this->assertSame('contacto', $resultado['nombre']);
    $this->assertSame(10, $resultado['limite']);
    $this->assertFalse($resultado['permitido']);
}

public function test_envio_de_mensajes_aplica_limite_de_veinte(): void
{
    $this->consumirSolicitudes(
        'POST',
        '/api/consultas/123/mensajes',
        '203.0.113.89',
        20
    );

    $resultado = RateLimitMiddleware::evaluarSolicitud(
        'POST',
        '/api/consultas/123/mensajes',
        '203.0.113.89',
        1000,
        $this->directorio
    );

    $this->assertSame('enviar_mensaje', $resultado['nombre']);
    $this->assertSame(20, $resultado['limite']);
    $this->assertFalse($resultado['permitido']);
}

public function test_rutas_admin_aplican_limite_especifico(): void
{
    $resultado = RateLimitMiddleware::evaluarSolicitud(
        'GET',
        '/api/admin/consultas',
        '203.0.113.90',
        1000,
        $this->directorio
    );

    $this->assertSame('admin', $resultado['nombre']);
    $this->assertSame(30, $resultado['limite']);
    $this->assertSame(29, $resultado['restantes']);
    $this->assertTrue($resultado['permitido']);
}

}