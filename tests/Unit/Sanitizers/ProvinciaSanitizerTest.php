<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\ProvinciaSanitizer;
use Tests\TestCase;

class ProvinciaSanitizerTest extends TestCase
{
    public function test_sanitizar_crear(): void
    {
        $resultado = ProvinciaSanitizer::sanitizarCrear(['nombre' => '  buenos   aires ']);

        $this->assertSame('Buenos Aires', $resultado['nombre']);
    }

    public function test_sanitizar_crear_without_nombre(): void
    {
        $resultado = ProvinciaSanitizer::sanitizarCrear([]);

        $this->assertNull($resultado['nombre']);
    }

    public function test_sanitizar_id_null_and_empty(): void
    {
        $this->assertNull(ProvinciaSanitizer::sanitizarId(null));
        $this->assertNull(ProvinciaSanitizer::sanitizarId(''));
    }

    public function test_sanitizar_id_valid_and_invalid(): void
    {
        $this->assertSame(2, ProvinciaSanitizer::sanitizarId('2'));
        $this->assertNull(ProvinciaSanitizer::sanitizarId('abc'));
        $this->assertNull(ProvinciaSanitizer::sanitizarId('0'));
    }

    public function test_sanitizar_nombre_non_string_returns_null(): void
    {
        $this->assertNull(ProvinciaSanitizer::sanitizarNombre(123));
    }

    public function test_sanitizar_nombre_formats_and_truncates(): void
    {
        $this->assertSame('Santa Fe', ProvinciaSanitizer::sanitizarNombre('  santa   fe '));
        $this->assertSame(
            100,
            mb_strlen(ProvinciaSanitizer::sanitizarNombre(str_repeat('a', 150)))
        );
    }

    public function test_sanitizar_actualizar(): void
    {
        $resultado = ProvinciaSanitizer::sanitizarActualizar(['nombre' => 'cordoba']);

        $this->assertSame(['nombre' => 'Cordoba'], $resultado);
    }

    public function test_sanitizar_actualizar_without_nombre(): void
    {
        $this->assertSame([], ProvinciaSanitizer::sanitizarActualizar([]));
    }
}
