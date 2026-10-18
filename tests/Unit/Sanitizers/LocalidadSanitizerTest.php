<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\LocalidadSanitizer;
use Tests\TestCase;

class LocalidadSanitizerTest extends TestCase
{
    public function test_sanitizar_localidad_full_payload(): void
    {
        $resultado = LocalidadSanitizer::sanitizarLocalidad([
            'id' => '4',
            'nombre' => '  san   fernando ',
            'codigo_postal' => ' 1646 ',
            'provincia_id' => '2',
        ]);

        $this->assertSame(4, $resultado['id']);
        $this->assertSame('San Fernando', $resultado['nombre']);
        $this->assertSame('1646', $resultado['codigo_postal']);
        $this->assertSame(2, $resultado['provincia_id']);
    }

    public function test_sanitizar_actualizacion_localidad_partial(): void
    {
        $resultado = LocalidadSanitizer::sanitizarActualizacionLocalidad([
            'nombre' => 'tigre',
        ]);

        $this->assertSame(['nombre' => 'Tigre'], $resultado);
    }

    public function test_sanitizar_actualizacion_localidad_all_fields(): void
    {
        $resultado = LocalidadSanitizer::sanitizarActualizacionLocalidad([
            'nombre' => 'tigre',
            'codigo_postal' => '1648',
            'provincia_id' => '1',
        ]);

        $this->assertSame('Tigre', $resultado['nombre']);
        $this->assertSame('1648', $resultado['codigo_postal']);
        $this->assertSame(1, $resultado['provincia_id']);
    }

    public function test_sanitizar_id_localidad(): void
    {
        $this->assertSame(9, LocalidadSanitizer::sanitizarIdLocalidad('9'));
        $this->assertNull(LocalidadSanitizer::sanitizarIdLocalidad(null));
        $this->assertNull(LocalidadSanitizer::sanitizarIdLocalidad(''));
        $this->assertNull(LocalidadSanitizer::sanitizarIdLocalidad('abc'));
        $this->assertNull(LocalidadSanitizer::sanitizarIdLocalidad('0'));
    }

    public function test_sanitizar_nombre_localidad(): void
    {
        $this->assertSame(
            'Villa Gesell',
            LocalidadSanitizer::sanitizarNombreLocalidad('  villa   gesell ')
        );
        $this->assertNull(LocalidadSanitizer::sanitizarNombreLocalidad(123));
    }

    public function test_sanitizar_codigo_postal_casts_numeric(): void
    {
        $this->assertSame('1646', LocalidadSanitizer::sanitizarCodigoPostal(1646));
    }

    public function test_sanitizar_codigo_postal_empty_returns_null(): void
    {
        $this->assertNull(LocalidadSanitizer::sanitizarCodigoPostal(null));
        $this->assertNull(LocalidadSanitizer::sanitizarCodigoPostal('   '));
    }

    public function test_sanitizar_codigo_postal_trims_and_truncates(): void
    {
        $this->assertSame('B1646', LocalidadSanitizer::sanitizarCodigoPostal(' B1646 '));
        $this->assertSame(
            15,
            mb_strlen(LocalidadSanitizer::sanitizarCodigoPostal(str_repeat('9', 20)))
        );
    }

    public function test_sanitizar_provincia_id(): void
    {
        $this->assertSame(3, LocalidadSanitizer::sanitizarProvinciaId('3'));
        $this->assertNull(LocalidadSanitizer::sanitizarProvinciaId(null));
        $this->assertNull(LocalidadSanitizer::sanitizarProvinciaId('abc'));
        $this->assertNull(LocalidadSanitizer::sanitizarProvinciaId('0'));
    }
}
