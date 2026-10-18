<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\ServicioSanitizer;
use Tests\TestCase;

class ServicioSanitizerTest extends TestCase
{
    public function test_sanitizar(): void
    {
        $resultado = ServicioSanitizer::sanitizar(['id' => '3', 'nombre' => '  <b>Wifi</b> ']);

        $this->assertSame(3, $resultado['id']);
        $this->assertSame('Wifi', $resultado['nombre']);
    }

    public function test_sanitizar_empty(): void
    {
        $resultado = ServicioSanitizer::sanitizar([]);

        $this->assertNull($resultado['id']);
        $this->assertNull($resultado['nombre']);
    }

    public function test_sanitizar_id(): void
    {
        $this->assertSame(4, ServicioSanitizer::sanitizarId('4'));
        $this->assertNull(ServicioSanitizer::sanitizarId(null));
        $this->assertNull(ServicioSanitizer::sanitizarId(''));
        $this->assertNull(ServicioSanitizer::sanitizarId('abc'));
        $this->assertNull(ServicioSanitizer::sanitizarId('0'));
    }

    public function test_sanitizar_nombre_null_and_empty(): void
    {
        $this->assertNull(ServicioSanitizer::sanitizarNombre(null));
        $this->assertNull(ServicioSanitizer::sanitizarNombre(''));
    }

    public function test_sanitizar_nombre_formats(): void
    {
        $this->assertSame('agua caliente', ServicioSanitizer::sanitizarNombre('  agua   caliente '));
    }

    public function test_sanitizar_actualizacion(): void
    {
        $resultado = ServicioSanitizer::sanitizarActualizacion(['nombre' => ' gas ']);

        $this->assertSame('gas', $resultado['nombre']);
    }

    public function test_sanitizar_actualizacion_without_nombre(): void
    {
        $resultado = ServicioSanitizer::sanitizarActualizacion([]);

        $this->assertNull($resultado['nombre']);
    }
}
