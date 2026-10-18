<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\ConsultaSanitizer;
use Tests\TestCase;

class ConsultaSanitizerTest extends TestCase
{
    public function test_sanitizar_consulta_full_payload(): void
    {
        $resultado = ConsultaSanitizer::sanitizarConsulta([
            'id' => '1',
            'propiedad_id' => '2',
            'usuario_id' => '3',
            'mensaje' => '<b>Hola</b>  mundo',
            'fecha_consulta' => '2024-05-01 10:30:00',
            'perfil_interesado' => [
                'fecha_mudanza' => '2024-06-01',
                'cantidad_ocupantes' => '2',
                'tiene_mascotas' => 'true',
                'cantidad_mascotas' => '1',
                'garantias' => ['  propietario ', 'propietario', 5],
            ],
        ]);

        $this->assertSame(1, $resultado['id']);
        $this->assertSame(2, $resultado['propiedad_id']);
        $this->assertSame(3, $resultado['usuario_id']);
        $this->assertSame('Hola mundo', $resultado['mensaje']);
        $this->assertSame('2024-05-01 10:30:00', $resultado['fecha_consulta']);
        $this->assertSame('2024-06-01', $resultado['perfil_interesado']['fecha_mudanza']);
        $this->assertSame(2, $resultado['perfil_interesado']['cantidad_ocupantes']);
        $this->assertTrue($resultado['perfil_interesado']['tiene_mascotas']);
        $this->assertSame(1, $resultado['perfil_interesado']['cantidad_mascotas']);
        $this->assertSame(['propietario', 5], $resultado['perfil_interesado']['garantias']);
    }

    public function test_sanitizar_id_rejects_invalid(): void
    {
        $this->assertNull(ConsultaSanitizer::sanitizarId(null));
        $this->assertNull(ConsultaSanitizer::sanitizarId(''));
        $this->assertNull(ConsultaSanitizer::sanitizarId('abc'));
        $this->assertNull(ConsultaSanitizer::sanitizarId('0'));
    }

    public function test_sanitizar_mensaje_blank_returns_null(): void
    {
        $this->assertNull(ConsultaSanitizer::sanitizarMensaje(null));
        $this->assertNull(ConsultaSanitizer::sanitizarMensaje(''));
    }

    public function test_sanitizar_fecha_valid(): void
    {
        $this->assertSame(
            '2024-05-01 00:00:00',
            ConsultaSanitizer::sanitizarFecha('2024-05-01')
        );
    }

    public function test_sanitizar_fecha_invalid_returns_null(): void
    {
        $this->assertNull(ConsultaSanitizer::sanitizarFecha('no-es-fecha'));
    }

    public function test_sanitizar_fecha_null_returns_null(): void
    {
        $this->assertNull(ConsultaSanitizer::sanitizarFecha(null));
        $this->assertNull(ConsultaSanitizer::sanitizarFecha(''));
    }

    public function test_sanitizar_perfil_null_returns_null(): void
    {
        $this->assertNull(ConsultaSanitizer::sanitizarPerfilInteresado(null));
    }

    public function test_sanitizar_perfil_non_array_returns_as_is(): void
    {
        $this->assertSame('perfil', ConsultaSanitizer::sanitizarPerfilInteresado('perfil'));
    }

    public function test_sanitizar_perfil_with_non_array_garantias(): void
    {
        $resultado = ConsultaSanitizer::sanitizarPerfilInteresado([
            'garantias' => 'sin-garantias',
        ]);

        $this->assertSame('sin-garantias', $resultado['garantias']);
    }

    public function test_sanitizar_perfil_with_non_string_fecha_mudanza(): void
    {
        $resultado = ConsultaSanitizer::sanitizarPerfilInteresado([
            'fecha_mudanza' => 123,
        ]);

        $this->assertNull($resultado['fecha_mudanza']);
    }

    public function test_sanitizar_perfil_with_invalid_fecha_mudanza(): void
    {
        $resultado = ConsultaSanitizer::sanitizarPerfilInteresado([
            'fecha_mudanza' => '2024-13-40',
        ]);

        $this->assertNull($resultado['fecha_mudanza']);
    }

    public function test_sanitizar_perfil_defaults_when_missing(): void
    {
        $resultado = ConsultaSanitizer::sanitizarPerfilInteresado([]);

        $this->assertNull($resultado['fecha_mudanza']);
        $this->assertNull($resultado['cantidad_ocupantes']);
        $this->assertFalse($resultado['tiene_mascotas']);
        $this->assertNull($resultado['cantidad_mascotas']);
        $this->assertSame([], $resultado['garantias']);
    }
}
