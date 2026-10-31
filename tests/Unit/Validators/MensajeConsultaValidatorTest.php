<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\MensajeConsultaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MensajeConsultaValidatorTest extends TestCase
{
    public function test_validar_mensaje_consulta_ok(): void
    {
        $resultado = MensajeConsultaValidator::validarMensajeConsulta([
            'consulta_id' => 5,
            'mensaje' => 'Hola, quería consultar por la propiedad',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_mensaje_consulta_requiriendo_id_sin_id_falla(): void
    {
        $resultado = MensajeConsultaValidator::validarMensajeConsulta(
            [
                'consulta_id' => 5,
                'mensaje' => 'Hola, quería consultar por la propiedad',
            ],
            true
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'id' =>
                    'El ID de mensaje es requerido. Debe ser un entero positivo.',
            ],
            $resultado['errors']
        );
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_mensaje_consulta_con_id_invalido_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = MensajeConsultaValidator::validarMensajeConsulta(
            [
                'id' => $id,
                'consulta_id' => 5,
                'mensaje' => 'Hola, quería consultar por la propiedad',
            ],
            true
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }

    public static function idsInvalidos(): array
    {
        return [
            'vacio' => [
                '',
                'El ID de mensaje es requerido. Debe ser un entero positivo.',
            ],
            'texto' => ['abc', 'El ID de mensaje debe ser positivo'],
            'cero' => [0, 'El ID de mensaje debe ser positivo'],
            'negativo' => [-1, 'El ID de mensaje debe ser positivo'],
        ];
    }

    public function test_validar_mensaje_consulta_ok_con_id_valido(): void
    {
        $resultado = MensajeConsultaValidator::validarMensajeConsulta(
            [
                'id' => 8,
                'consulta_id' => 5,
                'mensaje' => 'Hola, quería consultar por la propiedad',
            ],
            true
        );

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('consultasIdsInvalidos')]
    public function test_validar_mensaje_consulta_con_consulta_id_invalido_falla(
        mixed $consultaId,
        string $errorEsperado
    ): void {
        $resultado = MensajeConsultaValidator::validarMensajeConsulta([
            'consulta_id' => $consultaId,
            'mensaje' => 'Hola, quería consultar por la propiedad',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['consulta_id' => $errorEsperado],
            $resultado['errors']
        );
    }

    public static function consultasIdsInvalidos(): array
    {
        return [
            'ausente' => [null, 'El ID de la consulta es obligatorio'],
            'vacio' => ['', 'El ID de la consulta es obligatorio'],
            'cero' => [0, 'El ID de la consulta es obligatorio'],
            'texto' => [
                'abc',
                'El ID de la consulta debe ser un entero positivo',
            ],
            'negativo' => [
                -2,
                'El ID de la consulta debe ser un entero positivo',
            ],
        ];
    }

    #[DataProvider('mensajesInvalidos')]
    public function test_validar_mensaje_consulta_con_mensaje_invalido_falla(
        mixed $mensaje,
        string $errorEsperado
    ): void {
        $resultado = MensajeConsultaValidator::validarMensajeConsulta([
            'consulta_id' => 5,
            'mensaje' => $mensaje,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(['mensaje' => $errorEsperado], $resultado['errors']);
    }

    public static function mensajesInvalidos(): array
    {
        return [
            'ausente' => [null, 'El mensaje es obligatorio'],
            'vacio' => ['', 'El mensaje es obligatorio'],
            'solo_espacios' => [
                '    ',
                'El mensaje debe tener al menos 1 caracter',
            ],
            'muy_largo' => [
                str_repeat('a', 2001),
                'El mensaje no puede superar los 2000 caracteres',
            ],
            'profanidad' => [
                'puta',
                'El mensaje contiene lenguaje inapropiado y no puede ser publicado',
            ],
        ];
    }

    public function test_validar_mensaje_consulta_con_varios_errores_acumula(): void
    {
        $resultado = MensajeConsultaValidator::validarMensajeConsulta([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'consulta_id' => 'El ID de la consulta es obligatorio',
                'mensaje' => 'El mensaje es obligatorio',
            ],
            $resultado['errors']
        );
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_id_requerido_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = MensajeConsultaValidator::validarIdRequerido($id, 'mensaje');

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public function test_validar_id_requerido_ok(): void
    {
        $resultado = MensajeConsultaValidator::validarIdRequerido(4, 'mensaje');

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public function test_validar_id_requerido_sin_campo_indica_el_nombre_vacio(): void
    {
        $resultado = MensajeConsultaValidator::validarIdRequerido(null);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            'El ID de  es requerido. Debe ser un entero positivo.',
            $resultado['error']
        );
    }

    public function test_validar_crear_mensaje_consulta_ok(): void
    {
        $resultado = MensajeConsultaValidator::validarCrearMensajeConsulta([
            'consulta_id' => 5,
            'mensaje' => 'Hola, quería consultar por la propiedad',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_crear_mensaje_consulta_no_requiere_id(): void
    {
        $resultado = MensajeConsultaValidator::validarCrearMensajeConsulta([
            'consulta_id' => 5,
            'mensaje' => 'Hola, quería consultar por la propiedad',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_crear_mensaje_consulta_invalido_falla(): void
    {
        $resultado = MensajeConsultaValidator::validarCrearMensajeConsulta([
            'consulta_id' => 5,
            'mensaje' => '',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['mensaje' => 'El mensaje es obligatorio'],
            $resultado['errors']
        );
    }

    public function test_validar_actualizar_mensaje_consulta_requiere_id(): void
    {
        $resultado = MensajeConsultaValidator::validarActualizarMensajeConsulta([
            'consulta_id' => 5,
            'mensaje' => 'Hola, quería consultar por la propiedad',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertArrayHasKey('id', $resultado['errors']);
    }

    public function test_validar_actualizar_mensaje_consulta_ok(): void
    {
        $resultado = MensajeConsultaValidator::validarActualizarMensajeConsulta([
            'id' => 8,
            'consulta_id' => 5,
            'mensaje' => 'Hola, quería consultar por la propiedad',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_solo_id_mensaje_consulta_ok(): void
    {
        $resultado = MensajeConsultaValidator::validarSoloIdMensajeConsulta(6);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_solo_id_mensaje_consulta_null_falla(): void
    {
        $resultado = MensajeConsultaValidator::validarSoloIdMensajeConsulta(null);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(
            [
                'id' =>
                    'El ID de mensaje es requerido. Debe ser un entero positivo.',
            ],
            $resultado['errors']
        );
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_solo_id_mensaje_consulta_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = MensajeConsultaValidator::validarSoloIdMensajeConsulta($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }

    public function test_validar_paginacion_vacia_ok(): void
    {
        $resultado = MensajeConsultaValidator::validarPaginacion([]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_paginacion_con_limite_valido_ok(): void
    {
        $resultado = MensajeConsultaValidator::validarPaginacion([
            'limite' => '25',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('limitesInvalidos')]
    public function test_validar_paginacion_con_limite_invalido_falla(
        mixed $limite
    ): void {
        $resultado = MensajeConsultaValidator::validarPaginacion([
            'limite' => $limite,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['limite' => 'El límite debe ser un entero entre 1 y 50'],
            $resultado['errors']
        );
    }

    public static function limitesInvalidos(): array
    {
        return [
            'cero' => [0],
            'cincuenta_y_uno' => [51],
            'texto' => ['muchos'],
            'arreglo' => [[10]],
            'negativo' => [-5],
        ];
    }

    #[DataProvider('idsCursorValidos')]
    public function test_validar_paginacion_con_cursor_valido_ok(
        array $data
    ): void {
        $resultado = MensajeConsultaValidator::validarPaginacion($data);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public static function idsCursorValidos(): array
    {
        return [
            'antes_de_id' => [['antes_de_id' => 10]],
            'despues_de_id' => [['despues_de_id' => 10]],
        ];
    }

    #[DataProvider('idsCursorInvalidos')]
    public function test_validar_paginacion_con_cursor_invalido_falla(
        string $campo,
        mixed $valor
    ): void {
        $resultado = MensajeConsultaValidator::validarPaginacion([
            $campo => $valor,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [$campo => 'El ID debe ser un entero positivo'],
            $resultado['errors']
        );
    }

    public static function idsCursorInvalidos(): array
    {
        return [
            'antes_de_id_cero' => ['antes_de_id', 0],
            'antes_de_id_texto' => ['antes_de_id', 'x'],
            'despues_de_id_negativo' => ['despues_de_id', -1],
            'despues_de_id_arreglo' => ['despues_de_id', [5]],
        ];
    }

    public function test_validar_paginacion_con_ambos_cursors_falla(): void
    {
        $resultado = MensajeConsultaValidator::validarPaginacion([
            'antes_de_id' => 10,
            'despues_de_id' => 20,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'cursor' =>
                    'No se pueden utilizar antes_de_id y despues_de_id juntos.',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_paginacion_con_ambos_cursors_invalidos_acumula_errores(): void
    {
        $resultado = MensajeConsultaValidator::validarPaginacion([
            'limite' => 0,
            'antes_de_id' => 0,
            'despues_de_id' => 'x',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'limite' => 'El límite debe ser un entero entre 1 y 50',
                'antes_de_id' => 'El ID debe ser un entero positivo',
                'despues_de_id' => 'El ID debe ser un entero positivo',
                'cursor' =>
                    'No se pueden utilizar antes_de_id y despues_de_id juntos.',
            ],
            $resultado['errors']
        );
    }
}
