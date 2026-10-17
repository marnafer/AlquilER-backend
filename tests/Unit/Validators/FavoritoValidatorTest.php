<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\FavoritoValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FavoritoValidatorTest extends TestCase
{
    public function test_validar_crear_ok(): void
    {
        $resultado = FavoritoValidator::validarCrear(['propiedad_id' => 4]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('propiedadIdsInvalidos')]
    public function test_validar_crear_falla(
        mixed $propiedadId,
        string $errorEsperado
    ): void {
        $resultado = FavoritoValidator::validarCrear([
            'propiedad_id' => $propiedadId,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['propiedad_id' => $errorEsperado],
            $resultado['errors']
        );
    }

    public static function propiedadIdsInvalidos(): array
    {
        return [
            'ausente' => [
                null,
                'El ID de propiedad es requerido. Debe ser un entero positivo',
            ],
            'cadena_numerica' => [
                '4',
                'El ID de propiedad debe ser un entero positivo',
            ],
            'cero' => [
                0,
                'El ID de propiedad debe ser un entero positivo',
            ],
            'negativo' => [
                -3,
                'El ID de propiedad debe ser un entero positivo',
            ],
            'texto' => [
                'propiedad',
                'El ID de propiedad debe ser un entero positivo',
            ],
        ];
    }

    public function test_validar_crear_sin_propiedad_id_falla(): void
    {
        $resultado = FavoritoValidator::validarCrear([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'propiedad_id' =>
                    'El ID de propiedad es requerido. Debe ser un entero positivo',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_solo_id_ok(): void
    {
        $resultado = FavoritoValidator::validarSoloId(8);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_solo_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = FavoritoValidator::validarSoloId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }

    public static function idsInvalidos(): array
    {
        return [
            'null' => [
                null,
                'El ID de propiedad es requerido. Debe ser un entero positivo',
            ],
            'vacio' => [
                '',
                'El ID de propiedad es requerido. Debe ser un entero positivo',
            ],
            'texto' => [
                'x',
                'El ID de propiedad debe ser un entero positivo',
            ],
            'cero' => [
                0,
                'El ID de propiedad debe ser un entero positivo',
            ],
        ];
    }

    public function test_validar_propiedad_id_ok(): void
    {
        $resultado = FavoritoValidator::validarPropiedadId(6);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_propiedad_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = FavoritoValidator::validarPropiedadId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public function test_validar_id_requerido_ok(): void
    {
        $resultado = FavoritoValidator::validarIdRequerido(11, 'relación');

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public function test_validar_id_requerido_sin_campo_indica_el_nombre_vacio(): void
    {
        $resultado = FavoritoValidator::validarIdRequerido(null);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            'El ID de  es requerido. Debe ser un entero positivo',
            $resultado['error']
        );
    }

    public function test_validar_id_requerido_nulo_indica_el_campo(): void
    {
        $resultado = FavoritoValidator::validarIdRequerido(null, 'relación');

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            'El ID de relación es requerido. Debe ser un entero positivo',
            $resultado['error']
        );
    }

    public function test_validar_id_requerido_que_no_es_entero_falla(): void
    {
        $resultado = FavoritoValidator::validarIdRequerido('9', 'relación');

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            'El ID de relación debe ser un entero positivo',
            $resultado['error']
        );
    }
}
