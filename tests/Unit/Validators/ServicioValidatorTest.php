<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\ServicioValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ServicioValidatorTest extends TestCase
{
    #[DataProvider('idsInvalidos')]
    public function test_validar_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            ServicioValidator::validarId($id)
        );
    }

    public static function idsInvalidos(): array
    {
        return [
            'null' => [null, 'El ID de servicio es requerido'],
            'vacio' => ['', 'El ID de servicio es requerido'],
            'texto' => ['abc', 'El ID de servicio debe ser numérico'],
            'cero' => [0, 'El ID de servicio debe ser mayor a cero'],
            'negativo' => [-5, 'El ID de servicio debe ser mayor a cero'],
            'decimal' => [
                2.5,
                'El ID de servicio debe ser un entero válido',
            ],
            'decimal_como_texto' => [
                '2.5',
                'El ID de servicio debe ser un entero válido',
            ],
        ];
    }

    #[DataProvider('idsValidos')]
    public function test_validar_id_ok(mixed $id): void
    {
        $this->assertNull(ServicioValidator::validarId($id));
    }

    public static function idsValidos(): array
    {
        return [
            'entero' => [1],
            'cadena_numerica' => ['1'],
            'grande' => [999],
        ];
    }

    #[DataProvider('nombresInvalidos')]
    public function test_validar_nombre_falla(
        ?string $nombre,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            ServicioValidator::validarNombre($nombre)
        );
    }

    public static function nombresInvalidos(): array
    {
        return [
            'null' => [null, 'El nombre del servicio es requerido'],
            'vacio' => ['', 'El nombre del servicio es requerido'],
            'corto' => [
                'ab',
                'El nombre debe tener al menos 3 caracteres',
            ],
            'largo' => [
                str_repeat('a', 51),
                'El nombre no puede superar los 50 caracteres',
            ],
            'con_signos' => [
                'Limpieza!',
                'El nombre solo puede contener letras, números, espacios, guiones y &',
            ],
        ];
    }

    #[DataProvider('nombresValidos')]
    public function test_validar_nombre_ok(string $nombre): void
    {
        $this->assertNull(ServicioValidator::validarNombre($nombre));
    }

    public static function nombresValidos(): array
    {
        return [
            'simple' => ['Limpieza'],
            'con_numeros' => ['Limpieza 24h'],
            'con_guion' => ['Check-in rápido'],
            'con_ampersand' => ['Limpieza & mantenimiento'],
            'maximo' => [str_repeat('a', 50)],
        ];
    }

    public function test_validar_ok(): void
    {
        $resultado = ServicioValidator::validar([
            'nombre' => 'Limpieza 24h',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_sin_id_cuando_no_es_requerido(): void
    {
        $resultado = ServicioValidator::validar([
            'nombre' => 'Limpieza',
        ], false);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_con_id_requerido_ok(): void
    {
        $resultado = ServicioValidator::validar([
            'id' => 3,
            'nombre' => 'Limpieza',
        ], true);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_con_id_requerido_invalido_falla(): void
    {
        $resultado = ServicioValidator::validar([
            'nombre' => 'Limpieza',
        ], true);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['id' => 'El ID de servicio es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_sin_nombre_falla(): void
    {
        $resultado = ServicioValidator::validar([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['nombre' => 'El nombre del servicio es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_con_varios_campos_invalidos_acumula_errores(): void
    {
        $resultado = ServicioValidator::validar(
            ['id' => 'x', 'nombre' => 'ab'],
            true
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'id' => 'El ID de servicio debe ser numérico',
                'nombre' => 'El nombre debe tener al menos 3 caracteres',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_solo_id_ok(): void
    {
        $resultado = ServicioValidator::validarSoloId(9);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_solo_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = ServicioValidator::validarSoloId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }
}
