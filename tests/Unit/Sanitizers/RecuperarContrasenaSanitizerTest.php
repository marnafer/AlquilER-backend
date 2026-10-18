<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\RecuperarContrasenaSanitizer;
use Tests\TestCase;

class RecuperarContrasenaSanitizerTest extends TestCase
{
    public function test_sanitizar_solicitud(): void
    {
        $resultado = RecuperarContrasenaSanitizer::sanitizarSolicitud([
            'email' => '  usuario@example.com ',
        ]);

        $this->assertSame('usuario@example.com', $resultado['email']);
    }

    public function test_sanitizar_solicitud_without_email(): void
    {
        $resultado = RecuperarContrasenaSanitizer::sanitizarSolicitud([]);

        $this->assertNull($resultado['email']);
    }

    public function test_sanitizar_restablecer(): void
    {
        $resultado = RecuperarContrasenaSanitizer::sanitizarRestablecer([
            'email' => 'usuario@example.com',
            'token' => '  abc123 ',
            'contrasena' => '  secreta ',
        ]);

        $this->assertSame('usuario@example.com', $resultado['email']);
        $this->assertSame('abc123', $resultado['token']);
        $this->assertSame('secreta', $resultado['contrasena']);
    }

    public function test_sanitizar_restablecer_without_fields(): void
    {
        $resultado = RecuperarContrasenaSanitizer::sanitizarRestablecer([]);

        $this->assertNull($resultado['email']);
        $this->assertNull($resultado['token']);
        $this->assertSame('', $resultado['contrasena']);
    }

    public function test_sanitizar_token(): void
    {
        $this->assertSame('abc', RecuperarContrasenaSanitizer::sanitizarToken('  abc '));
        $this->assertNull(RecuperarContrasenaSanitizer::sanitizarToken('   '));
        $this->assertNull(RecuperarContrasenaSanitizer::sanitizarToken(null));
    }
}
