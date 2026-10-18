<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\PropiedadImagenSanitizer;
use Tests\TestCase;

class PropiedadImagenSanitizerTest extends TestCase
{
    public function test_sanitizar_id_accepts_valid(): void
    {
        $this->assertSame(5, PropiedadImagenSanitizer::sanitizarId('5'));
        $this->assertSame(5, PropiedadImagenSanitizer::sanitizarId(5));
    }

    public function test_sanitizar_id_rejects_non_string_non_int(): void
    {
        $this->assertNull(PropiedadImagenSanitizer::sanitizarId(1.5));
        $this->assertNull(PropiedadImagenSanitizer::sanitizarId(null));
    }

    public function test_sanitizar_id_rejects_non_digits(): void
    {
        $this->assertNull(PropiedadImagenSanitizer::sanitizarId('5a'));
    }

    public function test_sanitizar_id_rejects_zero(): void
    {
        $this->assertNull(PropiedadImagenSanitizer::sanitizarId('0'));
    }

    public function test_sanitizar_id_propiedad_imagen_delegates(): void
    {
        $this->assertSame(7, PropiedadImagenSanitizer::sanitizarIdPropiedadImagen('7'));
    }

    public function test_sanitizar_id_propiedad_delegates(): void
    {
        $this->assertSame(8, PropiedadImagenSanitizer::sanitizarIdPropiedad('8'));
    }

    public function test_sanitizar_descripcion_valid(): void
    {
        $this->assertSame('frente', PropiedadImagenSanitizer::sanitizarDescripcion('  frente '));
    }

    public function test_sanitizar_descripcion_non_string_returns_null(): void
    {
        $this->assertNull(PropiedadImagenSanitizer::sanitizarDescripcion(123));
    }

    public function test_sanitizar_descripcion_blank_returns_null(): void
    {
        $this->assertNull(PropiedadImagenSanitizer::sanitizarDescripcion('   '));
    }

    public function test_sanitizar_descripcion_truncates(): void
    {
        $this->assertSame(
            300,
            mb_strlen(PropiedadImagenSanitizer::sanitizarDescripcion(str_repeat('a', 400)))
        );
    }

    public function test_sanitizar_propiedad_imagen(): void
    {
        $resultado = PropiedadImagenSanitizer::sanitizarPropiedadImagen([
            'propiedad_id' => '9',
            'descripcion' => '  living ',
        ]);

        $this->assertSame(9, $resultado['propiedad_id']);
        $this->assertSame('living', $resultado['descripcion']);
    }

    public function test_sanitizar_propiedad_imagen_empty(): void
    {
        $resultado = PropiedadImagenSanitizer::sanitizarPropiedadImagen([]);

        $this->assertNull($resultado['propiedad_id']);
        $this->assertNull($resultado['descripcion']);
    }
}
