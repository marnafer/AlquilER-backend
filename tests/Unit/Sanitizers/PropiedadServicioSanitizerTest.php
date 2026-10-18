<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\PropiedadServicioSanitizer;
use Tests\TestCase;

class PropiedadServicioSanitizerTest extends TestCase
{
    public function test_sanitizar_full_payload(): void
    {
        $resultado = PropiedadServicioSanitizer::sanitizar([
            'id' => '1',
            'propiedad_id' => '2',
            'servicio_id' => '3',
        ]);

        $this->assertSame(1, $resultado['id']);
        $this->assertSame(2, $resultado['propiedad_id']);
        $this->assertSame(3, $resultado['servicio_id']);
    }

    public function test_sanitizar_empty(): void
    {
        $resultado = PropiedadServicioSanitizer::sanitizar([]);

        $this->assertNull($resultado['id']);
        $this->assertNull($resultado['propiedad_id']);
        $this->assertNull($resultado['servicio_id']);
    }

    public function test_sanitizar_id(): void
    {
        $this->assertSame(4, PropiedadServicioSanitizer::sanitizarId('4'));
        $this->assertNull(PropiedadServicioSanitizer::sanitizarId(null));
        $this->assertNull(PropiedadServicioSanitizer::sanitizarId(''));
        $this->assertNull(PropiedadServicioSanitizer::sanitizarId('abc'));
        $this->assertNull(PropiedadServicioSanitizer::sanitizarId('0'));
    }

    public function test_sanitizar_servicio_ids_maps_each_value(): void
    {
        $resultado = PropiedadServicioSanitizer::sanitizarServicioIds(['1', 2, 'abc', '0']);

        $this->assertSame([1, 2, null, null], $resultado);
    }
}
