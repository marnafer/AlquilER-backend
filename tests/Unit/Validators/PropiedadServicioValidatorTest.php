<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\PropiedadServicioValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PropiedadServicioValidatorTest extends TestCase
{
    public function test_validar_ok(): void
    {
        $resultado = PropiedadServicioValidator::validar([
            'propiedad_id' => 1,
            'servicio_id' => 2,
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_ok_requiriendo_id(): void
    {
        $resultado = PropiedadServicioValidator::validar(
            ['id' => 5, 'propiedad_id' => 1, 'servicio_id' => 2],
            true
        );

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_requiriendo_id_sin_id_falla(): void
    {
        $resultado = PropiedadServicioValidator::validar(
            ['propiedad_id' => 1, 'servicio_id' => 2],
            true
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'id' =>
                    'El ID de relación es requerido. Debe ser un entero positivo.',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_sin_propiedad_id_falla(): void
    {
        $resultado = PropiedadServicioValidator::validar([
            'servicio_id' => 2,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'propiedad_id' =>
                    'El ID de propiedad es requerido. Debe ser un entero positivo.',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_sin_servicio_id_falla(): void
    {
        $resultado = PropiedadServicioValidator::validar([
            'propiedad_id' => 1,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'servicio_id' =>
                    'El ID de servicio es requerido. Debe ser un entero positivo.',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_con_propiedad_id_cero_falla(): void
    {
        $resultado = PropiedadServicioValidator::validar([
            'propiedad_id' => 0,
            'servicio_id' => 2,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['propiedad_id' => 'El ID de propiedad debe ser positivo'],
            $resultado['errors']
        );
    }

    public function test_validar_con_servicio_id_decimal_falla(): void
    {
        $resultado = PropiedadServicioValidator::validar([
            'propiedad_id' => 1,
            'servicio_id' => 2.5,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['servicio_id' => 'El ID de servicio debe ser un entero válido'],
            $resultado['errors']
        );
    }

    public function test_validar_con_todos_los_campos_invalidos_acumula_errores(): void
    {
        $resultado = PropiedadServicioValidator::validar([], true);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'id' =>
                    'El ID de relación es requerido. Debe ser un entero positivo.',
                'propiedad_id' =>
                    'El ID de propiedad es requerido. Debe ser un entero positivo.',
                'servicio_id' =>
                    'El ID de servicio es requerido. Debe ser un entero positivo.',
            ],
            $resultado['errors']
        );
    }

    #[DataProvider('idsRequeridosInvalidos')]
    public function test_validar_id_requerido_falla(
        mixed $id,
        string $campo,
        string $errorEsperado
    ): void {
        $resultado = PropiedadServicioValidator::validarIdRequerido(
            $id,
            $campo
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function idsRequeridosInvalidos(): array
    {
        return [
            'relacion_nulo' => [
                null,
                'relación',
                'El ID de relación es requerido. Debe ser un entero positivo.',
            ],
            'relacion_vacio' => [
                '',
                'relación',
                'El ID de relación es requerido. Debe ser un entero positivo.',
            ],
            'relacion_texto' => [
                'x',
                'relación',
                'El ID de relación debe ser positivo',
            ],
            'relacion_cero' => [
                0,
                'relación',
                'El ID de relación debe ser positivo',
            ],
            'relacion_decimal' => [
                1.5,
                'relación',
                'El ID de relación debe ser un entero válido',
            ],
            'sin_campo_texto' => [
                'abc',
                '',
                'El ID de  debe ser positivo',
            ],
        ];
    }

    #[DataProvider('idsValidos')]
    public function test_validar_id_requerido_ok(mixed $id): void
    {
        $resultado = PropiedadServicioValidator::validarIdRequerido(
            $id,
            'relación'
        );

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function idsValidos(): array
    {
        return [
            'entero' => [7],
            'cadena_numerica' => ['7'],
        ];
    }

    #[DataProvider('idsPropiedadInvalidos')]
    public function test_validar_propiedad_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = PropiedadServicioValidator::validarPropiedadId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function idsPropiedadInvalidos(): array
    {
        return [
            'null' => [
                null,
                'El ID de propiedad es requerido. Debe ser un entero positivo.',
            ],
            'texto' => [
                'abc',
                'El ID de propiedad debe ser positivo',
            ],
            'cero' => [
                0,
                'El ID de propiedad debe ser positivo',
            ],
            'decimal' => [
                3.5,
                'El ID de propiedad debe ser un entero válido',
            ],
        ];
    }

    #[DataProvider('idsValidos')]
    public function test_validar_propiedad_id_ok(mixed $id): void
    {
        $resultado = PropiedadServicioValidator::validarPropiedadId($id);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    #[DataProvider('idsServicioInvalidos')]
    public function test_validar_servicio_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = PropiedadServicioValidator::validarServicioId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function idsServicioInvalidos(): array
    {
        return [
            'null' => [
                null,
                'El ID de servicio es requerido. Debe ser un entero positivo.',
            ],
            'texto' => [
                'abc',
                'El ID de servicio debe ser positivo',
            ],
            'negativo' => [
                -1,
                'El ID de servicio debe ser positivo',
            ],
            'decimal' => [
                2.5,
                'El ID de servicio debe ser un entero válido',
            ],
        ];
    }

    #[DataProvider('idsValidos')]
    public function test_validar_servicio_id_ok(mixed $id): void
    {
        $resultado = PropiedadServicioValidator::validarServicioId($id);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public function test_validar_servicio_ids_vacios_falla(): void
    {
        $resultado = PropiedadServicioValidator::validarServicioIds([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'servicio_ids' =>
                    'Debe proporcionar al menos un ID de servicio',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_servicio_ids_vacios_permitidos_es_ok(): void
    {
        $resultado = PropiedadServicioValidator::validarServicioIds([], true);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_servicio_ids_ok(): void
    {
        $resultado = PropiedadServicioValidator::validarServicioIds([1, '2', 3]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('listasConIdsInvalidos')]
    public function test_validar_servicio_ids_con_id_invalido_falla(
        array $ids,
        string $claveEsperada,
        string $errorEsperado
    ): void {
        $resultado = PropiedadServicioValidator::validarServicioIds($ids);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame($errorEsperado, $resultado['errors'][$claveEsperada]);
    }

    public static function listasConIdsInvalidos(): array
    {
        return [
            'sin_primer_elemento' => [
                [null],
                'servicio_ids.0',
                'El ID de servicio es requerido. Debe ser un entero positivo.',
            ],
            'con_texto' => [
                [1, 'x'],
                'servicio_ids.1',
                'El ID de servicio debe ser positivo',
            ],
            'con_cero' => [
                [0],
                'servicio_ids.0',
                'El ID de servicio debe ser positivo',
            ],
            'con_decimal' => [
                [1.5],
                'servicio_ids.0',
                'El ID de servicio debe ser un entero válido',
            ],
        ];
    }

    #[DataProvider('listasDuplicadas')]
    public function test_validar_servicio_ids_con_duplicados_falla(
        array $ids,
        int $cantidadDeErrores
    ): void {
        $resultado = PropiedadServicioValidator::validarServicioIds($ids);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            'No se permiten IDs de servicio duplicados',
            $resultado['errors']['servicio_ids']
        );
        $this->assertCount($cantidadDeErrores, $resultado['errors']);
    }

    public static function listasDuplicadas(): array
    {
        return [
            'enteros' => [[1, 1], 1],
            'entero_y_cadena' => [[1, '1'], 1],
            'texto_invalido_duplicado' => [['x', 'x'], 3],
        ];
    }

    public function test_validar_crear_ok(): void
    {
        $resultado = PropiedadServicioValidator::validarCrear([
            'propiedad_id' => 1,
            'servicio_id' => 2,
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_crear_ignora_el_id_de_relacion(): void
    {
        $resultado = PropiedadServicioValidator::validarCrear([
            'propiedad_id' => 1,
            'servicio_id' => 2,
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_crear_invalido_falla(): void
    {
        $resultado = PropiedadServicioValidator::validarCrear([
            'propiedad_id' => null,
            'servicio_id' => 2,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertArrayHasKey('propiedad_id', $resultado['errors']);
    }

    public function test_validar_actualizar_requiere_id(): void
    {
        $resultado = PropiedadServicioValidator::validarActualizar([
            'propiedad_id' => 1,
            'servicio_id' => 2,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertArrayHasKey('id', $resultado['errors']);
    }

    public function test_validar_actualizar_ok(): void
    {
        $resultado = PropiedadServicioValidator::validarActualizar([
            'id' => 9,
            'propiedad_id' => 1,
            'servicio_id' => 2,
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_solo_id_ok(): void
    {
        $resultado = PropiedadServicioValidator::validarSoloId(4);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsInvalidosParaSoloId')]
    public function test_validar_solo_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = PropiedadServicioValidator::validarSoloId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }

    public static function idsInvalidosParaSoloId(): array
    {
        return [
            'nulo' => [
                null,
                'El ID de relación es requerido. Debe ser un entero positivo.',
            ],
            'vacio' => [
                '',
                'El ID de relación es requerido. Debe ser un entero positivo.',
            ],
            'texto' => [
                'x',
                'El ID de relación debe ser positivo',
            ],
            'cero' => [
                0,
                'El ID de relación debe ser positivo',
            ],
            'decimal' => [
                2.5,
                'El ID de relación debe ser un entero válido',
            ],
        ];
    }
}
