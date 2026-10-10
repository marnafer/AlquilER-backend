<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\ConsultaValidator;
use App\Validators\PropiedadValidator;
use App\Sanitizers\ConsultaSanitizer;
use App\Sanitizers\PropiedadSanitizer;
use PHPUnit\Framework\TestCase;

class PrecalificacionValidatorTest extends TestCase
{
    public function test_valida_los_datos_de_un_interesado(): void
    {
        $errores = ConsultaValidator::validarPerfilInteresado([
            'fecha_mudanza' => '2026-11-01',
            'cantidad_ocupantes' => 2,
            'tiene_mascotas' => false,
            'cantidad_mascotas' => 0,
            'garantias' => ['seguro_caucion'],
        ]);

        $this->assertSame([], $errores);
    }

    public function test_rechaza_datos_de_interesado_inconsistentes(): void
    {
        $errores = ConsultaValidator::validarPerfilInteresado([
            'fecha_mudanza' => '2026-02-31',
            'cantidad_ocupantes' => 0,
            'tiene_mascotas' => true,
            'cantidad_mascotas' => 0,
            'garantias' => ['garantia_inventada'],
        ]);

        $this->assertCount(4, $errores);
    }

    public function test_valida_criterios_configurables_por_el_propietario(): void
    {
        $errores = PropiedadValidator::validarRequisitosInteresados([
            'fecha_disponible_desde' => '2026-11-01',
            'max_ocupantes' => 4,
            'garantias_aceptadas' => ['recibo_sueldo', 'garante'],
        ]);

        $this->assertSame([], $errores);
    }

    public function test_rechaza_criterios_de_propietario_invalidos(): void
    {
        $errores = PropiedadValidator::validarRequisitosInteresados([
            'fecha_disponible_desde' => '2026-13-40',
            'max_ocupantes' => 51,
            'garantias_aceptadas' => ['garantia_inventada'],
        ]);

        $this->assertCount(3, $errores);
    }

    public function test_ignora_ingreso_en_los_datos_de_interesado_y_requisitos(): void
    {
        $perfil = ConsultaSanitizer::sanitizarPerfilInteresado([
            'fecha_mudanza' => '2026-11-01',
            'cantidad_ocupantes' => 2,
            'ingreso_mensual' => 850000,
            'tiene_mascotas' => false,
            'cantidad_mascotas' => 0,
            'garantias' => [],
        ]);
        $requisitos = PropiedadSanitizer::sanitizarRequisitosInteresados([
            'max_ocupantes' => 4,
            'ingreso_minimo' => 900000,
            'garantias_aceptadas' => [],
        ]);

        $this->assertArrayNotHasKey('ingreso_mensual', $perfil);
        $this->assertArrayNotHasKey('ingreso_minimo', $requisitos);
    }
}
