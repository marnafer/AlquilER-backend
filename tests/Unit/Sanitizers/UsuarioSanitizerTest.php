<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\UsuarioSanitizer;
use Tests\TestCase;

class UsuarioSanitizerTest extends TestCase
{
    public function test_sanitizar_usuario_full_payload(): void
    {
        $resultado = UsuarioSanitizer::sanitizarUsuario([
            'nombre' => '  juan  ',
            'apellido' => '  perez ',
            'email' => '  JUAN@Example.com ',
            'telefono' => ' +54 (11) 1234-5678 ',
            'domicilio' => '  av.   siempreviva 123 ',
            'contrasena' => 'secreta',
        ]);

        $this->assertSame('Juan', $resultado['nombre']);
        $this->assertSame('Perez', $resultado['apellido']);
        $this->assertSame('juan@example.com', $resultado['email']);
        $this->assertSame('+54 (11) 1234-5678', $resultado['telefono']);
        $this->assertSame('av. siempreviva 123', $resultado['domicilio']);
        $this->assertSame('secreta', $resultado['contrasena']);
    }

    public function test_sanitizar_usuario_empty(): void
    {
        $resultado = UsuarioSanitizer::sanitizarUsuario([]);

        $this->assertNull($resultado['nombre']);
        $this->assertNull($resultado['apellido']);
        $this->assertNull($resultado['email']);
        $this->assertNull($resultado['telefono']);
        $this->assertNull($resultado['domicilio']);
        $this->assertNull($resultado['contrasena']);
    }

    public function test_sanitizar_id_usuario(): void
    {
        $this->assertSame(5, UsuarioSanitizer::sanitizarIdUsuario('5'));
        $this->assertNull(UsuarioSanitizer::sanitizarIdUsuario(null));
        $this->assertNull(UsuarioSanitizer::sanitizarIdUsuario(''));
        $this->assertNull(UsuarioSanitizer::sanitizarIdUsuario('abc'));
        $this->assertNull(UsuarioSanitizer::sanitizarIdUsuario('0'));
    }

    public function test_sanitizar_nombre_strips_invalid_characters(): void
    {
        $this->assertSame('Juan', UsuarioSanitizer::sanitizarNombre('juan123'));
        $this->assertNull(UsuarioSanitizer::sanitizarNombre(''));
        $this->assertNull(UsuarioSanitizer::sanitizarNombre(null));
    }

    public function test_sanitizar_apellido_strips_invalid_characters(): void
    {
        $this->assertSame('Perez', UsuarioSanitizer::sanitizarApellido('perez456'));
        $this->assertNull(UsuarioSanitizer::sanitizarApellido(''));
    }

    public function test_sanitizar_email(): void
    {
        $this->assertSame('usuario@example.com', UsuarioSanitizer::sanitizarEmail(' USUARIO@EXAMPLE.COM '));
        $this->assertNull(UsuarioSanitizer::sanitizarEmail(''));
        $this->assertNull(UsuarioSanitizer::sanitizarEmail(null));
    }

    public function test_sanitizar_telefono_strips_invalid_characters(): void
    {
        $this->assertSame('+54111234', UsuarioSanitizer::sanitizarTelefono('+54abc111234'));
        $this->assertNull(UsuarioSanitizer::sanitizarTelefono(''));
    }

    public function test_sanitizar_domicilio(): void
    {
        $this->assertSame('calle falsa 123', UsuarioSanitizer::sanitizarDomicilio('  calle   falsa 123 '));
        $this->assertNull(UsuarioSanitizer::sanitizarDomicilio(''));
    }

    public function test_sanitizar_rol_id(): void
    {
        $this->assertSame(2, UsuarioSanitizer::sanitizarRolId('2'));
        $this->assertNull(UsuarioSanitizer::sanitizarRolId(null));
        $this->assertNull(UsuarioSanitizer::sanitizarRolId(''));
        $this->assertNull(UsuarioSanitizer::sanitizarRolId('abc'));
    }

    public function test_sanitizar_fecha_eliminacion_valid(): void
    {
        $this->assertSame(
            '2024-05-01 10:00:00',
            UsuarioSanitizer::sanitizarFechaEliminacion('2024-05-01 10:00:00')
        );
    }

    public function test_sanitizar_fecha_eliminacion_invalid(): void
    {
        $this->assertNull(UsuarioSanitizer::sanitizarFechaEliminacion('no-es-fecha'));
    }

    public function test_sanitizar_fecha_eliminacion_empty(): void
    {
        $this->assertNull(UsuarioSanitizer::sanitizarFechaEliminacion(''));
        $this->assertNull(UsuarioSanitizer::sanitizarFechaEliminacion(null));
    }

    public function test_sanitizar_solo_email_delegates(): void
    {
        $this->assertSame('a@b.com', UsuarioSanitizer::sanitizarSoloEmail(' A@B.com '));
    }

    public function test_sanitizar_solo_contrasena(): void
    {
        $this->assertSame('secreta', UsuarioSanitizer::sanitizarSoloContrasena('secreta'));
        $this->assertNull(UsuarioSanitizer::sanitizarSoloContrasena(''));
        $this->assertNull(UsuarioSanitizer::sanitizarSoloContrasena(null));
    }

    public function test_sanitizar_actualizacion_full(): void
    {
        $resultado = UsuarioSanitizer::sanitizarActualizacion([
            'nombre' => 'ana',
            'apellido' => 'gomez',
            'email' => 'ANA@EXAMPLE.COM',
            'telefono' => '123',
            'domicilio' => 'calle 1',
            'rol_id' => '3',
            'contrasena' => 'nueva',
        ]);

        $this->assertSame('Ana', $resultado['nombre']);
        $this->assertSame('Gomez', $resultado['apellido']);
        $this->assertSame('ana@example.com', $resultado['email']);
        $this->assertSame('123', $resultado['telefono']);
        $this->assertSame('calle 1', $resultado['domicilio']);
        $this->assertSame(3, $resultado['rol_id']);
        $this->assertSame('nueva', $resultado['contrasena']);
    }

    public function test_sanitizar_actualizacion_partial_and_empty(): void
    {
        $this->assertSame([], UsuarioSanitizer::sanitizarActualizacion([]));
        $this->assertSame(
            ['nombre' => 'Ana'],
            UsuarioSanitizer::sanitizarActualizacion(['nombre' => 'ana'])
        );
    }
}
