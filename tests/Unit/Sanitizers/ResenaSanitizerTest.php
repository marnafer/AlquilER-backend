<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\ResenaSanitizer;
use Tests\TestCase;

class ResenaSanitizerTest extends TestCase
{
    public function test_sanitizar_crear(): void
    {
        $resultado = ResenaSanitizer::sanitizarCrear([
            'reserva_id' => '10',
            'calificacion' => '5',
            'comentario' => '  <b>Excelente</b>   lugar ',
        ]);

        $this->assertSame(10, $resultado['reserva_id']);
        $this->assertSame(5, $resultado['calificacion']);
        $this->assertSame('Excelente lugar', $resultado['comentario']);
    }

    public function test_sanitizar_crear_empty(): void
    {
        $resultado = ResenaSanitizer::sanitizarCrear([]);

        $this->assertNull($resultado['reserva_id']);
        $this->assertNull($resultado['calificacion']);
        $this->assertNull($resultado['comentario']);
    }

    public function test_sanitizar_actualizar(): void
    {
        $resultado = ResenaSanitizer::sanitizarActualizar([
            'id' => '3',
            'calificacion' => '4',
            'comentario' => '  bueno ',
        ]);

        $this->assertSame(3, $resultado['id']);
        $this->assertSame(4, $resultado['calificacion']);
        $this->assertSame('bueno', $resultado['comentario']);
    }

    public function test_sanitizar_id(): void
    {
        $this->assertSame(1, ResenaSanitizer::sanitizarId('1'));
        $this->assertNull(ResenaSanitizer::sanitizarId(null));
        $this->assertNull(ResenaSanitizer::sanitizarId(''));
        $this->assertNull(ResenaSanitizer::sanitizarId('abc'));
        $this->assertNull(ResenaSanitizer::sanitizarId('0'));
    }

    public function test_sanitizar_calificacion(): void
    {
        $this->assertSame(3, ResenaSanitizer::sanitizarCalificacion('3'));
        $this->assertNull(ResenaSanitizer::sanitizarCalificacion(null));
        $this->assertNull(ResenaSanitizer::sanitizarCalificacion(''));
        $this->assertNull(ResenaSanitizer::sanitizarCalificacion('abc'));
    }

    public function test_sanitizar_comentario(): void
    {
        $this->assertSame('muy bueno', ResenaSanitizer::sanitizarComentario('  muy   bueno '));
        $this->assertNull(ResenaSanitizer::sanitizarComentario(null));
        $this->assertNull(ResenaSanitizer::sanitizarComentario('   '));
    }
}
