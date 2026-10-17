<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\RolValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RolValidatorTest extends TestCase
{
    #[DataProvider('idsDeRolInvalidos')]
    public function test_validar_id_rol_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = RolValidator::validarIdRol($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function idsDeRolInvalidos(): array
    {
        return [
            'null' => [null, 'El ID de rol es requerido'],
            'vacio' => ['', 'El ID de rol es requerido'],
            'texto' => ['abc', 'El ID de rol debe ser numérico'],
            'cero' => [0, 'El ID de rol debe ser positivo'],
            'negativo' => [-1, 'El ID de rol debe ser positivo'],
        ];
    }

    #[DataProvider('idsDeRolValidos')]
    public function test_validar_id_rol_ok(mixed $id): void
    {
        $resultado = RolValidator::validarIdRol($id);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function idsDeRolValidos(): array
    {
        return [
            'entero' => [3],
            'cadena_numerica' => ['3'],
        ];
    }

    #[DataProvider('nombresInvalidos')]
    public function test_validar_nombre_falla(
        ?string $nombre,
        string $errorEsperado
    ): void {
        $resultado = RolValidator::validarNombre($nombre);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function nombresInvalidos(): array
    {
        return [
            'null' => [null, 'El nombre del rol es requerido'],
            'vacio' => ['', 'El nombre del rol es requerido'],
            'corto' => [
                'ab',
                'El nombre debe tener al menos 3 caracteres',
            ],
            'largo' => [
                str_repeat('a', 31),
                'El nombre no puede exceder los 30 caracteres',
            ],
            'con_numeros' => [
                'Admin 1',
                'El nombre solo puede contener letras y espacios',
            ],
            'con_signos' => [
                'rol!',
                'El nombre solo puede contener letras y espacios',
            ],
        ];
    }

    #[DataProvider('nombresValidos')]
    public function test_validar_nombre_ok(string $nombre): void
    {
        $resultado = RolValidator::validarNombre($nombre);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function nombresValidos(): array
    {
        return [
            'simple' => ['Administrador'],
            'con_espacios' => ['Propietario de inmueble'],
            'con_tildes' => ['Arrendatario'],
            'maximo' => [str_repeat('a', 30)],
        ];
    }

    public function test_validar_rol_ok(): void
    {
        $resultado = RolValidator::validarRol(['nombre' => 'Inquilino']);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_rol_sin_id_cuando_no_es_requerido(): void
    {
        $resultado = RolValidator::validarRol(
            ['nombre' => 'Inquilino'],
            false
        );

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_rol_con_id_requerido_ok(): void
    {
        $resultado = RolValidator::validarRol(
            ['id' => 2, 'nombre' => 'Inquilino'],
            true
        );

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_rol_con_id_requerido_invalido_falla(): void
    {
        $resultado = RolValidator::validarRol(
            ['nombre' => 'Inquilino'],
            true
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['id' => 'El ID de rol es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_rol_sin_nombre_falla(): void
    {
        $resultado = RolValidator::validarRol([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['nombre' => 'El nombre del rol es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_rol_con_varios_campos_invalidos_acumula_errores(): void
    {
        $resultado = RolValidator::validarRol(
            ['id' => 0, 'nombre' => 'x'],
            true
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'id' => 'El ID de rol debe ser positivo',
                'nombre' => 'El nombre debe tener al menos 3 caracteres',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_solo_id_rol_ok(): void
    {
        $resultado = RolValidator::validarSoloIdRol(7);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsDeRolInvalidos')]
    public function test_validar_solo_id_rol_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = RolValidator::validarSoloIdRol($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }
}
