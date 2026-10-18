<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\FavoritoSanitizer;
use Tests\TestCase;

class FavoritoSanitizerTest extends TestCase
{
    public function test_sanitizar_crear_with_valid_id(): void
    {
        $resultado = FavoritoSanitizer::sanitizarCrear(['propiedad_id' => '10']);

        $this->assertSame(10, $resultado['propiedad_id']);
    }

    public function test_sanitizar_crear_with_invalid_id(): void
    {
        $resultado = FavoritoSanitizer::sanitizarCrear(['propiedad_id' => 'abc']);

        $this->assertNull($resultado['propiedad_id']);
    }

    public function test_sanitizar_id_null_and_empty(): void
    {
        $this->assertNull(FavoritoSanitizer::sanitizarId(null));
        $this->assertNull(FavoritoSanitizer::sanitizarId(''));
    }

    public function test_sanitizar_id_rejects_non_positive(): void
    {
        $this->assertNull(FavoritoSanitizer::sanitizarId('0'));
        $this->assertNull(FavoritoSanitizer::sanitizarId('-3'));
    }

    public function test_sanitizar_propiedad_id_delegates(): void
    {
        $this->assertSame(5, FavoritoSanitizer::sanitizarPropiedadId('5'));
        $this->assertNull(FavoritoSanitizer::sanitizarPropiedadId('abc'));
    }
}
