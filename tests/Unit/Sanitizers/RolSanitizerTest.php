<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\RolSanitizer;
use Tests\TestCase;

class RolSanitizerTest extends TestCase
{
    public function test_sanitizar_id_rol_accepts_valid(): void
    {
        $this->assertSame(2, RolSanitizer::sanitizarIdRol('2'));
        $this->assertSame(2, RolSanitizer::sanitizarIdRol(2));
    }

    public function test_sanitizar_id_rol_rejects_non_string_non_int(): void
    {
        $this->assertNull(RolSanitizer::sanitizarIdRol(1.5));
        $this->assertNull(RolSanitizer::sanitizarIdRol(null));
    }

    public function test_sanitizar_id_rol_rejects_non_digits_and_zero(): void
    {
        $this->assertNull(RolSanitizer::sanitizarIdRol('2a'));
        $this->assertNull(RolSanitizer::sanitizarIdRol('0'));
    }

    public function test_sanitizar_nombre_non_string_returns_null(): void
    {
        $this->assertNull(RolSanitizer::sanitizarNombre(123));
    }

    public function test_sanitizar_nombre_formats_and_truncates(): void
    {
        $this->assertSame('Administrador', RolSanitizer::sanitizarNombre('  administrador '));
        $this->assertSame(
            30,
            mb_strlen(RolSanitizer::sanitizarNombre(str_repeat('a', 40)))
        );
    }

    public function test_sanitizar_rol(): void
    {
        $resultado = RolSanitizer::sanitizarRol(['id' => '1', 'nombre' => 'admin']);

        $this->assertSame(1, $resultado['id']);
        $this->assertSame('Admin', $resultado['nombre']);
    }

    public function test_sanitizar_rol_with_empty_nombre(): void
    {
        $resultado = RolSanitizer::sanitizarRol(['nombre' => '']);

        $this->assertNull($resultado['nombre']);
    }

    public function test_sanitizar_actualizacion_rol(): void
    {
        $resultado = RolSanitizer::sanitizarActualizacionRol(['nombre' => 'inquilino']);

        $this->assertSame('Inquilino', $resultado['nombre']);
    }

    public function test_sanitizar_actualizacion_rol_without_nombre(): void
    {
        $resultado = RolSanitizer::sanitizarActualizacionRol([]);

        $this->assertNull($resultado['nombre']);
    }
}
