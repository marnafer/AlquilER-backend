<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\ReservaSanitizer;
use Tests\TestCase;

class ReservaSanitizerTest extends TestCase
{
    public function test_sanitizar_crear(): void
    {
        $resultado = ReservaSanitizer::sanitizarCrear([
            'propiedad_id' => '10',
            'fecha_inicio_alquiler' => '2024-05-01',
            'fecha_fin_alquiler' => '2024-06-01',
        ]);

        $this->assertSame(10, $resultado['propiedad_id']);
        $this->assertSame('2024-05-01', $resultado['fecha_inicio_alquiler']);
        $this->assertSame('2024-06-01', $resultado['fecha_fin_alquiler']);
    }

    public function test_sanitizar_crear_empty(): void
    {
        $resultado = ReservaSanitizer::sanitizarCrear([]);

        $this->assertNull($resultado['propiedad_id']);
        $this->assertNull($resultado['fecha_inicio_alquiler']);
        $this->assertNull($resultado['fecha_fin_alquiler']);
    }

    public function test_sanitizar_fecha_valid(): void
    {
        $this->assertSame('2024-05-01', ReservaSanitizer::sanitizarFecha(' 2024-05-01 '));
    }

    public function test_sanitizar_fecha_invalid_format_returns_null(): void
    {
        $this->assertNull(ReservaSanitizer::sanitizarFecha('01/05/2024'));
    }

    public function test_sanitizar_fecha_null_and_empty(): void
    {
        $this->assertNull(ReservaSanitizer::sanitizarFecha(null));
        $this->assertNull(ReservaSanitizer::sanitizarFecha('   '));
    }

    public function test_sanitizar_id(): void
    {
        $this->assertSame(4, ReservaSanitizer::sanitizarId('4'));
        $this->assertNull(ReservaSanitizer::sanitizarId(null));
        $this->assertNull(ReservaSanitizer::sanitizarId(''));
        $this->assertNull(ReservaSanitizer::sanitizarId('abc'));
        $this->assertNull(ReservaSanitizer::sanitizarId('0'));
    }

    public function test_sanitizar_estado(): void
    {
        $this->assertSame('confirmada', ReservaSanitizer::sanitizarEstado('  CONFIRMADA '));
        $this->assertNull(ReservaSanitizer::sanitizarEstado(null));
        $this->assertNull(ReservaSanitizer::sanitizarEstado('   '));
    }

    public function test_sanitizar_reserva_id_delegates(): void
    {
        $this->assertSame(6, ReservaSanitizer::sanitizarReservaId('6'));
        $this->assertNull(ReservaSanitizer::sanitizarReservaId('abc'));
    }

    public function test_sanitizar_actualizar_estado(): void
    {
        $resultado = ReservaSanitizer::sanitizarActualizarEstado(['estado' => 'PENDIENTE']);

        $this->assertSame('pendiente', $resultado['estado']);
    }

    public function test_sanitizar_actualizar_estado_without_estado(): void
    {
        $resultado = ReservaSanitizer::sanitizarActualizarEstado([]);

        $this->assertNull($resultado['estado']);
    }
}
