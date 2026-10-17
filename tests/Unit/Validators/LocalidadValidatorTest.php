<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\LocalidadValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LocalidadValidatorTest extends TestCase
{
    #[DataProvider('idsLocalidadInvalidos')]
    public function test_validar_id_localidad_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = LocalidadValidator::validarIdLocalidad($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function idsLocalidadInvalidos(): array
    {
        return [
            'null' => [
                null,
                'El ID de localidad es requerido. Debe ser un numero entero positivo.',
            ],
            'vacio' => [
                '',
                'El ID de localidad es requerido. Debe ser un numero entero positivo.',
            ],
            'texto' => ['abc', 'El ID de localidad debe ser numérico'],
            'cero' => [0, 'El ID de localidad debe ser positivo'],
            'negativo' => [-2, 'El ID de localidad debe ser positivo'],
        ];
    }

    #[DataProvider('idsValidos')]
    public function test_validar_id_localidad_ok(mixed $id): void
    {
        $resultado = LocalidadValidator::validarIdLocalidad($id);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function idsValidos(): array
    {
        return [
            'entero' => [15],
            'cadena_numerica' => ['15'],
        ];
    }

    #[DataProvider('nombresLocalidadInvalidos')]
    public function test_validar_nombre_localidad_falla(
        ?string $nombre,
        string $errorEsperado
    ): void {
        $resultado = LocalidadValidator::validarNombreLocalidad($nombre);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function nombresLocalidadInvalidos(): array
    {
        return [
            'null' => [null, 'El nombre es requerido'],
            'vacio' => ['', 'El nombre es requerido'],
            'solo_espacios' => ['   ', 'El nombre debe tener al menos 2 caracteres'],
            'una_letra' => ['a', 'El nombre debe tener al menos 2 caracteres'],
            'muy_largo' => [
                str_repeat('a', 101),
                'El nombre no puede exceder los 100 caracteres',
            ],
            'caracteres_no_permitidos' => [
                'Calle@1',
                'El nombre solo puede contener letras, números, espacios, guiones y &',
            ],
        ];
    }

    public function test_validar_nombre_localidad_ok(): void
    {
        $resultado = LocalidadValidator::validarNombreLocalidad(
            '  San Martín 1-2.5 (B)  '
        );

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    #[DataProvider('codigosPostalesInvalidos')]
    public function test_validar_codigo_postal_falla(
        ?string $codigoPostal,
        string $errorEsperado
    ): void {
        $resultado = LocalidadValidator::validarCodigoPostal($codigoPostal);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function codigosPostalesInvalidos(): array
    {
        return [
            'null' => [null, 'El código postal es requerido'],
            'vacio' => ['', 'El código postal es requerido'],
            'un_caracter' => ['1', 'El código postal debe tener al menos 2 caracteres'],
            'muy_largo' => [
                str_repeat('9', 16),
                'El código postal no puede exceder los 15 caracteres',
            ],
            'formato' => [
                'AB@CD',
                'Formato de código postal inválido',
            ],
        ];
    }

    #[DataProvider('codigosPostalesValidos')]
    public function test_validar_codigo_postal_ok(string $codigoPostal): void
    {
        $resultado = LocalidadValidator::validarCodigoPostal($codigoPostal);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function codigosPostalesValidos(): array
    {
        return [
            'numerico' => ['1900'],
            'alfanumerico' => ['B1675A'],
            'con_espacios' => ['  B 1675 '],
        ];
    }

    #[DataProvider('provinciasIdInvalidas')]
    public function test_validar_provincia_id_falla(
        mixed $provinciaId,
        string $errorEsperado
    ): void {
        $resultado = LocalidadValidator::validarProvinciaId($provinciaId);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function provinciasIdInvalidas(): array
    {
        return [
            'null' => [null, 'La provincia es requerida'],
            'vacio' => ['', 'La provincia es requerida'],
            'texto' => ['abc', 'Provincia inválida'],
            'cero' => [0, 'Provincia inválida'],
            'negativo' => [-1, 'Provincia inválida'],
        ];
    }

    #[DataProvider('idsValidos')]
    public function test_validar_provincia_id_ok(mixed $provinciaId): void
    {
        $resultado = LocalidadValidator::validarProvinciaId($provinciaId);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public function test_validar_localidad_ok(): void
    {
        $resultado = LocalidadValidator::validarLocalidad([
            'id' => 3,
            'nombre' => 'La Plata',
            'codigo_postal' => '1900',
            'provincia_id' => 1,
        ], true);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_localidad_sin_campos_es_ok(): void
    {
        $resultado = LocalidadValidator::validarLocalidad([]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_localidad_requiriendo_id_sin_id_falla(): void
    {
        $resultado = LocalidadValidator::validarLocalidad([], true);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'id' =>
                    'El ID de localidad es requerido. Debe ser un numero entero positivo.',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_localidad_con_nombre_invalido_falla(): void
    {
        $resultado = LocalidadValidator::validarLocalidad([
            'nombre' => '',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['nombre' => 'El nombre es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_localidad_con_codigo_postal_invalido_falla(): void
    {
        $resultado = LocalidadValidator::validarLocalidad([
            'codigo_postal' => 'x',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['codigo_postal' => 'El código postal debe tener al menos 2 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_localidad_con_provincia_invalida_falla(): void
    {
        $resultado = LocalidadValidator::validarLocalidad([
            'provincia_id' => 0,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['provincia_id' => 'Provincia inválida'],
            $resultado['errors']
        );
    }

    public function test_validar_localidad_con_todos_los_campos_invalidos_acumula_errores(): void
    {
        $resultado = LocalidadValidator::validarLocalidad(
            [
                'id' => 'x',
                'nombre' => 'a',
                'codigo_postal' => 'A@1',
                'provincia_id' => 'abc',
            ],
            true
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'id' => 'El ID de localidad debe ser numérico',
                'nombre' => 'El nombre debe tener al menos 2 caracteres',
                'codigo_postal' => 'Formato de código postal inválido',
                'provincia_id' => 'Provincia inválida',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_solo_id_localidad_ok(): void
    {
        $resultado = LocalidadValidator::validarSoloIdLocalidad(9);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsLocalidadInvalidos')]
    public function test_validar_solo_id_localidad_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = LocalidadValidator::validarSoloIdLocalidad($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }
}
