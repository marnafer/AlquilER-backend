<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\ProvinciaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProvinciaValidatorTest extends TestCase
{
    #[DataProvider('idsInvalidos')]
    public function test_validar_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            ProvinciaValidator::validarId($id)
        );
    }

    public static function idsInvalidos(): array
    {
        return [
            'null' => [null, 'El ID de provincia es requerido'],
            'vacio' => ['', 'El ID de provincia es requerido'],
            'texto' => ['abc', 'El ID de provincia debe ser numérico'],
            'cero' => [0, 'El ID de provincia debe ser positivo'],
            'negativo' => [-1, 'El ID de provincia debe ser positivo'],
            'decimal' => [1.5, 'El ID de provincia debe ser un número entero'],
        ];
    }

    #[DataProvider('idsValidos')]
    public function test_validar_id_ok(mixed $id): void
    {
        $this->assertNull(ProvinciaValidator::validarId($id));
    }

    public static function idsValidos(): array
    {
        return [
            'entero' => [6],
            'cadena_numerica' => ['6'],
        ];
    }

    #[DataProvider('nombresInvalidos')]
    public function test_validar_nombre_falla(
        ?string $nombre,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            ProvinciaValidator::validarNombre($nombre)
        );
    }

    public static function nombresInvalidos(): array
    {
        return [
            'null' => [null, 'El nombre es requerido'],
            'vacio' => ['', 'El nombre es requerido'],
            'corto' => ['ab', 'El nombre debe tener al menos 3 caracteres'],
            'con_espacios_al_costado' => [
                '  ab  ',
                'El nombre debe tener al menos 3 caracteres',
            ],
            'largo' => [
                str_repeat('a', 101),
                'El nombre no puede exceder los 100 caracteres',
            ],
            'con_numeros' => [
                'Buenos Aires 1',
                'El nombre solo puede contener letras y espacios',
            ],
        ];
    }

    #[DataProvider('nombresValidos')]
    public function test_validar_nombre_ok(string $nombre): void
    {
        $this->assertNull(ProvinciaValidator::validarNombre($nombre));
    }

    public static function nombresValidos(): array
    {
        return [
            'simple' => ['Córdoba'],
            'con_espacios' => ['  San Luis  '],
            'con_unicode' => ['Tierra del Fuego'],
        ];
    }

    public function test_validar_ok(): void
    {
        $resultado = ProvinciaValidator::validar([
            'nombre' => 'Mendoza',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_sin_nombre_falla(): void
    {
        $resultado = ProvinciaValidator::validar([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['nombre' => 'El nombre es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_con_nombre_invalido_falla(): void
    {
        $resultado = ProvinciaValidator::validar([
            'nombre' => 'Mendoza 1',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['nombre' => 'El nombre solo puede contener letras y espacios'],
            $resultado['errors']
        );
    }

    public function test_validar_solo_id_ok(): void
    {
        $resultado = ProvinciaValidator::validarSoloId(2);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_solo_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = ProvinciaValidator::validarSoloId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }
}
