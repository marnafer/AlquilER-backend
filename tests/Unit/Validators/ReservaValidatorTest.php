<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\ReservaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReservaValidatorTest extends TestCase
{
    #[DataProvider('idsInvalidos')]
    public function test_validar_id_falla(
        string $metodo,
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = ReservaValidator::{$metodo}($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function idsInvalidos(): array
    {
        $casos = [];

        $entidades = [
            'validarId' => 'reserva',
            'validarPropiedadId' => 'propiedad',
            'validarUsuarioId' => 'usuario',
        ];

        foreach ($entidades as $metodo => $entidad) {
            $casos["{$metodo}_nulo"] = [
                $metodo,
                null,
                "El ID de {$entidad} es requerido",
            ];
            $casos["{$metodo}_vacio"] = [
                $metodo,
                '',
                "El ID de {$entidad} es requerido",
            ];
            $casos["{$metodo}_texto"] = [
                $metodo,
                'abc',
                "El ID de {$entidad} debe ser numérico",
            ];
            $casos["{$metodo}_cero"] = [
                $metodo,
                0,
                "El ID de {$entidad} debe ser positivo",
            ];
            $casos["{$metodo}_negativo"] = [
                $metodo,
                -1,
                "El ID de {$entidad} debe ser positivo",
            ];
        }

        return $casos;
    }

    #[DataProvider('idsValidos')]
    public function test_validar_id_ok(
        string $metodo,
        mixed $id
    ): void {
        $resultado = ReservaValidator::{$metodo}($id);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function idsValidos(): array
    {
        $casos = [];

        foreach ([
            'validarId',
            'validarPropiedadId',
            'validarUsuarioId',
        ] as $metodo) {
            $casos["{$metodo}_entero"] = [$metodo, 5];
            $casos["{$metodo}_cadena_numerica"] = [$metodo, '5'];
        }

        return $casos;
    }

    #[DataProvider('estadosInvalidos')]
    public function test_validar_estado_falla(
        mixed $estado,
        string $errorEsperado
    ): void {
        $resultado = ReservaValidator::validarEstado($estado);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function estadosInvalidos(): array
    {
        return [
            'null' => [null, 'El estado es requerido'],
            'vacio' => ['', 'El estado es requerido'],
            'entero' => [1, 'El estado debe ser texto'],
            'no_permitido' => [
                'cancelada',
                'El estado debe ser pendiente, confirmada o rechazada',
            ],
            'con_mayusculas' => [
                'Pendiente',
                'El estado debe ser pendiente, confirmada o rechazada',
            ],
        ];
    }

    #[DataProvider('estadosValidos')]
    public function test_validar_estado_ok(string $estado): void
    {
        $resultado = ReservaValidator::validarEstado($estado);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function estadosValidos(): array
    {
        return [
            'pendiente' => ['pendiente'],
            'confirmada' => ['confirmada'],
            'rechazada' => ['rechazada'],
        ];
    }

    public function test_validar_crear_ok(): void
    {
        $resultado = ReservaValidator::validarCrear([
            'propiedad_id' => 3,
            'fecha_inicio_alquiler' => date('Y-m-d', strtotime('+1 day')),
            'fecha_fin_alquiler' => date('Y-m-d', strtotime('+10 days')),
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_crear_sin_propiedad_id_falla(): void
    {
        $resultado = ReservaValidator::validarCrear([
            'fecha_inicio_alquiler' => date('Y-m-d', strtotime('+1 day')),
            'fecha_fin_alquiler' => date('Y-m-d', strtotime('+10 days')),
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['propiedad_id' => 'El ID de propiedad es requerido'],
            $resultado['errors']
        );
    }

    #[DataProvider('fechasDeInicioInvalidas')]
    public function test_validar_crear_con_fecha_inicio_invalida_falla(
        ?string $fechaInicio,
        string $errorEsperado
    ): void {
        $resultado = ReservaValidator::validarCrear([
            'propiedad_id' => 3,
            'fecha_inicio_alquiler' => $fechaInicio,
            'fecha_fin_alquiler' => date('Y-m-d', strtotime('+10 days')),
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['fecha_inicio_alquiler' => $errorEsperado],
            $resultado['errors']
        );
    }

    public static function fechasDeInicioInvalidas(): array
    {
        return [
            'null' => [
                null,
                'La fecha de inicio del alquiler es requerida',
            ],
            'vacia' => [
                '',
                'La fecha de inicio del alquiler es requerida',
            ],
            'texto' => [
                'mañana',
                'La fecha de inicio del alquiler no es válida',
            ],
            'sin_formato' => [
                date('d/m/Y', strtotime('+1 day')),
                'La fecha de inicio del alquiler no es válida',
            ],
            'dia_inexistente' => [
                '2026-02-31',
                'La fecha de inicio del alquiler no es válida',
            ],
            'anterior_a_hoy' => [
                date('Y-m-d', strtotime('-1 day')),
                'La fecha de inicio no puede ser anterior a hoy',
            ],
        ];
    }

    #[DataProvider('fechasDeFinInvalidas')]
    public function test_validar_crear_con_fecha_fin_invalida_falla(
        ?string $fechaFin,
        string $errorEsperado
    ): void {
        $resultado = ReservaValidator::validarCrear([
            'propiedad_id' => 3,
            'fecha_inicio_alquiler' => date('Y-m-d', strtotime('+1 day')),
            'fecha_fin_alquiler' => $fechaFin,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['fecha_fin_alquiler' => $errorEsperado],
            $resultado['errors']
        );
    }

    public static function fechasDeFinInvalidas(): array
    {
        return [
            'null' => [null, 'La fecha de fin del alquiler es requerida'],
            'vacia' => ['', 'La fecha de fin del alquiler es requerida'],
            'texto' => [
                'una semana después',
                'La fecha de fin del alquiler no es válida',
            ],
            'sin_formato' => [
                date('d/m/Y', strtotime('+10 days')),
                'La fecha de fin del alquiler no es válida',
            ],
        ];
    }

    #[DataProvider('rangosDeFechasInvalidos')]
    public function test_validar_crear_con_rango_de_fechas_invalido_falla(
        string $fechaInicio,
        string $fechaFin
    ): void {
        $resultado = ReservaValidator::validarCrear([
            'propiedad_id' => 3,
            'fecha_inicio_alquiler' => $fechaInicio,
            'fecha_fin_alquiler' => $fechaFin,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'fecha_fin_alquiler' =>
                    'La fecha de fin debe ser posterior a la fecha de inicio',
            ],
            $resultado['errors']
        );
    }

    public static function rangosDeFechasInvalidos(): array
    {
        $inicio = date('Y-m-d', strtotime('+5 days'));

        return [
            'iguales' => [$inicio, $inicio],
            'fin_anterior' => [
                $inicio,
                date('Y-m-d', strtotime('+1 day')),
            ],
        ];
    }

    public function test_validar_crear_sin_fechas_acumula_ambos_errores(): void
    {
        $resultado = ReservaValidator::validarCrear([
            'propiedad_id' => 3,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'fecha_inicio_alquiler' =>
                    'La fecha de inicio del alquiler es requerida',
                'fecha_fin_alquiler' =>
                    'La fecha de fin del alquiler es requerida',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_crear_con_varios_campos_invalidos_acumula_errores(): void
    {
        $resultado = ReservaValidator::validarCrear([
            'propiedad_id' => 'x',
            'fecha_inicio_alquiler' => 'no-fecha',
            'fecha_fin_alquiler' => 'tampoco-fecha',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'propiedad_id' => 'El ID de propiedad debe ser numérico',
                'fecha_inicio_alquiler' =>
                    'La fecha de inicio del alquiler no es válida',
                'fecha_fin_alquiler' =>
                    'La fecha de fin del alquiler no es válida',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_actualizar_estado_ok(): void
    {
        $resultado = ReservaValidator::validarActualizarEstado([
            'estado' => 'confirmada',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('estadosInvalidos')]
    public function test_validar_actualizar_estado_falla(
        mixed $estado,
        string $errorEsperado
    ): void {
        $resultado = ReservaValidator::validarActualizarEstado([
            'estado' => $estado,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['estado' => $errorEsperado],
            $resultado['errors']
        );
    }

    public function test_validar_solo_id_ok(): void
    {
        $resultado = ReservaValidator::validarSoloId(12);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_solo_id_null_falla(): void
    {
        $resultado = ReservaValidator::validarSoloId(null);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(
            ['id' => 'El ID de reserva es requerido'],
            $resultado['errors']
        );
    }

    #[DataProvider('idsDeReservaInvalidos')]
    public function test_validar_solo_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = ReservaValidator::validarSoloId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }

    public static function idsDeReservaInvalidos(): array
    {
        return [
            'vacio' => ['', 'El ID de reserva es requerido'],
            'texto' => ['abc', 'El ID de reserva debe ser numérico'],
            'cero' => [0, 'El ID de reserva debe ser positivo'],
            'negativo' => [-3, 'El ID de reserva debe ser positivo'],
        ];
    }
}
