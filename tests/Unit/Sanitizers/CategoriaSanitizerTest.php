<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\CategoriaSanitizer;
use Tests\TestCase;

class CategoriaSanitizerTest extends TestCase
{
    public function test_sanitizar_id_categoria_accepts_valid_string(): void
    {
        $this->assertSame(7, CategoriaSanitizer::sanitizarIdCategoria('7'));
    }

    public function test_sanitizar_id_categoria_accepts_valid_int(): void
    {
        $this->assertSame(7, CategoriaSanitizer::sanitizarIdCategoria(7));
    }

    public function test_sanitizar_id_categoria_rejects_non_string_non_int(): void
    {
        $this->assertNull(CategoriaSanitizer::sanitizarIdCategoria(1.5));
        $this->assertNull(CategoriaSanitizer::sanitizarIdCategoria(null));
    }

    public function test_sanitizar_id_categoria_rejects_non_digits(): void
    {
        $this->assertNull(CategoriaSanitizer::sanitizarIdCategoria('12a'));
    }

    public function test_sanitizar_id_categoria_rejects_zero(): void
    {
        $this->assertNull(CategoriaSanitizer::sanitizarIdCategoria('0'));
    }

    public function test_sanitizar_nombre_formats_text(): void
    {
        $this->assertSame(
            'Casa De Playa',
            CategoriaSanitizer::sanitizarNombre('  casa   de   playa ')
        );
    }

    public function test_sanitizar_nombre_non_string_returns_null(): void
    {
        $this->assertNull(CategoriaSanitizer::sanitizarNombre(123));
    }

    public function test_sanitizar_nombre_truncates_to_50_chars(): void
    {
        $nombre = str_repeat('a', 60);

        $this->assertSame(50, mb_strlen(CategoriaSanitizer::sanitizarNombre($nombre)));
    }

    public function test_sanitizar_categoria_full_payload(): void
    {
        $resultado = CategoriaSanitizer::sanitizarCategoria([
            'id' => '3',
            'nombre' => 'departamento',
        ]);

        $this->assertSame(3, $resultado['id']);
        $this->assertSame('Departamento', $resultado['nombre']);
    }

    public function test_sanitizar_categoria_with_empty_nombre(): void
    {
        $resultado = CategoriaSanitizer::sanitizarCategoria(['nombre' => '']);

        $this->assertNull($resultado['nombre']);
    }

    public function test_sanitizar_actualizacion_categoria(): void
    {
        $resultado = CategoriaSanitizer::sanitizarActualizacionCategoria([
            'nombre' => 'casa',
        ]);

        $this->assertSame('Casa', $resultado['nombre']);
    }

    public function test_sanitizar_actualizacion_categoria_without_nombre(): void
    {
        $resultado = CategoriaSanitizer::sanitizarActualizacionCategoria([]);

        $this->assertNull($resultado['nombre']);
    }
}
