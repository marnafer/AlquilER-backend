<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\MensajeConsultaSanitizer;
use Tests\TestCase;

class MensajeConsultaSanitizerTest extends TestCase
{
    public function test_sanitizar_mensaje_consulta_full_payload(): void
    {
        $resultado = MensajeConsultaSanitizer::sanitizarMensajeConsulta([
            'id' => '1',
            'consulta_id' => '2',
            'usuario_id' => '3',
            'mensaje' => '<b>Hola</b>',
        ]);

        $this->assertSame(1, $resultado['id']);
        $this->assertSame(2, $resultado['consulta_id']);
        $this->assertSame(3, $resultado['usuario_id']);
        $this->assertSame('Hola', $resultado['mensaje']);
    }

    public function test_sanitizar_mensaje_consulta_empty(): void
    {
        $resultado = MensajeConsultaSanitizer::sanitizarMensajeConsulta([]);

        $this->assertNull($resultado['id']);
        $this->assertNull($resultado['consulta_id']);
        $this->assertNull($resultado['usuario_id']);
        $this->assertNull($resultado['mensaje']);
    }

    public function test_sanitizar_mensaje_texto_keeps_newlines(): void
    {
        $this->assertSame(
            "linea 1\nlinea 2",
            MensajeConsultaSanitizer::sanitizarMensajeTexto("linea 1\nlinea 2")
        );
    }

    public function test_sanitizar_mensaje_texto_null_and_empty(): void
    {
        $this->assertNull(MensajeConsultaSanitizer::sanitizarMensajeTexto(null));
        $this->assertNull(MensajeConsultaSanitizer::sanitizarMensajeTexto(''));
    }

    public function test_sanitizar_entero(): void
    {
        $this->assertSame(5, MensajeConsultaSanitizer::sanitizarEntero('5'));
        $this->assertNull(MensajeConsultaSanitizer::sanitizarEntero(null));
        $this->assertNull(MensajeConsultaSanitizer::sanitizarEntero(''));
        $this->assertNull(MensajeConsultaSanitizer::sanitizarEntero('abc'));
        $this->assertNull(MensajeConsultaSanitizer::sanitizarEntero('0'));
    }
}
