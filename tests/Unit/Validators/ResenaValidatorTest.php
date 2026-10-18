<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\ResenaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResenaValidatorTest extends TestCase
{
    #[DataProvider('casosDeIdsRequeridos')]
    public function test_validadores_de_id_falla(
        string $metodo,
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = ResenaValidator::$metodo($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function casosDeIdsRequeridos(): array
    {
        $casos = [];

        $entidades = [
            'validarId' => 'reseña',
            'validarReservaId' => 'reserva',
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
                -2,
                "El ID de {$entidad} debe ser positivo",
            ];
        }

        $casos['validarCalificadorId_nulo'] = [
            'validarCalificadorId',
            null,
            'El ID del calificador es requerido',
        ];
        $casos['validarCalificadorId_vacio'] = [
            'validarCalificadorId',
            '',
            'El ID del calificador es requerido',
        ];
        $casos['validarCalificadorId_texto'] = [
            'validarCalificadorId',
            'abc',
            'El ID del calificador debe ser numérico',
        ];
        $casos['validarCalificadorId_cero'] = [
            'validarCalificadorId',
            0,
            'El ID del calificador debe ser positivo',
        ];
        $casos['validarCalificadorId_negativo'] = [
            'validarCalificadorId',
            -2,
            'El ID del calificador debe ser positivo',
        ];

        return $casos;
    }

    #[DataProvider('casosDeIdsValidos')]
    public function test_validadores_de_id_ok(
        string $metodo,
        mixed $id
    ): void {
        $resultado = ResenaValidator::$metodo($id);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function casosDeIdsValidos(): array
    {
        $casos = [];

        foreach ([
            'validarId',
            'validarReservaId',
            'validarPropiedadId',
            'validarUsuarioId',
            'validarCalificadorId',
        ] as $metodo) {
            $casos["{$metodo}_entero"] = [$metodo, 4];
            $casos["{$metodo}_cadena_numerica"] = [$metodo, '4'];
        }

        return $casos;
    }

    #[DataProvider('calificacionesInvalidas')]
    public function test_validar_calificacion_falla(
        mixed $calificacion,
        string $errorEsperado
    ): void {
        $resultado = ResenaValidator::validarCalificacion($calificacion);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function calificacionesInvalidas(): array
    {
        return [
            'null' => [null, 'La calificación es requerida'],
            'vacia' => ['', 'La calificación es requerida'],
            'texto' => ['mucha', 'La calificación debe ser un número entero'],
            'decimal' => [4.5, 'La calificación debe ser un número entero'],
            'cero' => [0, 'La calificación debe estar entre 1 y 5'],
            'seis' => [6, 'La calificación debe estar entre 1 y 5'],
            'negativa' => [-1, 'La calificación debe estar entre 1 y 5'],
        ];
    }

    #[DataProvider('calificacionesValidas')]
    public function test_validar_calificacion_ok(mixed $calificacion): void
    {
        $resultado = ResenaValidator::validarCalificacion($calificacion);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function calificacionesValidas(): array
    {
        return [
            'minima' => [1],
            'maxima' => [5],
            'cadena_numerica' => ['3'],
        ];
    }

    #[DataProvider('comentariosInvalidos')]
    public function test_validar_comentario_falla(
        string $comentario,
        string $errorEsperado
    ): void {
        $resultado = ResenaValidator::validarComentario($comentario);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function comentariosInvalidos(): array
    {
        return [
            'vacio' => [
                '',
                'El comentario debe tener al menos 3 caracteres',
            ],
            'corto' => [
                'ab',
                'El comentario debe tener al menos 3 caracteres',
            ],
            'con_espacios_al_costado' => [
                '  ab  ',
                'El comentario debe tener al menos 3 caracteres',
            ],
            'largo' => [
                str_repeat('a', 1001),
                'El comentario no puede superar los 1000 caracteres',
            ],
        ];
    }

    #[DataProvider('comentariosValidos')]
    public function test_validar_comentario_ok(string $comentario): void
    {
        $resultado = ResenaValidator::validarComentario($comentario);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function comentariosValidos(): array
    {
        return [
            'simple' => ['Muy buena la propiedad'],
            'con_espacios_al_costado' => ['  Muy buena la propiedad  '],
            'minima' => ['abc'],
        ];
    }

    public function test_validar_crear_ok(): void
    {
        $resultado = ResenaValidator::validarCrear([
            'reserva_id' => 10,
            'calificacion' => 5,
            'comentario' => 'Excelente estadía, muy recomendable',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_crear_sin_comentario_es_ok(): void
    {
        $resultado = ResenaValidator::validarCrear([
            'reserva_id' => 10,
            'calificacion' => 4,
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_crear_con_comentario_en_blanco_lo_omite(): void
    {
        $resultado = ResenaValidator::validarCrear([
            'reserva_id' => 10,
            'calificacion' => 4,
            'comentario' => '',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_crear_sin_reserva_id_falla(): void
    {
        $resultado = ResenaValidator::validarCrear([
            'calificacion' => 5,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['reserva_id' => 'El ID de reserva es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_crear_con_calificacion_invalida_falla(): void
    {
        $resultado = ResenaValidator::validarCrear([
            'reserva_id' => 10,
            'calificacion' => 7,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['calificacion' => 'La calificación debe estar entre 1 y 5'],
            $resultado['errors']
        );
    }

    public function test_validar_crear_con_comentario_corto_falla(): void
    {
        $resultado = ResenaValidator::validarCrear([
            'reserva_id' => 10,
            'calificacion' => 5,
            'comentario' => 'ab',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['comentario' => 'El comentario debe tener al menos 3 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_crear_con_varios_campos_invalidos_acumula_errores(): void
    {
        $resultado = ResenaValidator::validarCrear([
            'reserva_id' => 'x',
            'calificacion' => 'mucha',
            'comentario' => 'ab',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'reserva_id' => 'El ID de reserva debe ser numérico',
                'calificacion' => 'La calificación debe ser un número entero',
                'comentario' => 'El comentario debe tener al menos 3 caracteres',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_solo_id_ok(): void
    {
        $resultado = ResenaValidator::validarSoloId(6);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsDeResenaInvalidos')]
    public function test_validar_solo_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = ResenaValidator::validarSoloId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }

    public static function idsDeResenaInvalidos(): array
    {
        return [
            'null' => [null, 'El ID de reseña es requerido'],
            'vacio' => ['', 'El ID de reseña es requerido'],
            'texto' => ['abc', 'El ID de reseña debe ser numérico'],
            'cero' => [0, 'El ID de reseña debe ser positivo'],
        ];
    }
}
