<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\CategoriaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CategoriaValidatorTest extends TestCase
{
    #[DataProvider('idsValidos')]
    public function test_validar_id_categoria_ok(mixed $id): void
    {
        $resultado = CategoriaValidator::validarIdCategoria($id);

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

    #[DataProvider('idsInvalidos')]
    public function test_validar_id_categoria_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = CategoriaValidator::validarIdCategoria($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function idsInvalidos(): array
    {
        return [
            'null' => [null, 'El ID de categoría es requerido'],
            'vacio' => ['', 'El ID de categoría es requerido'],
            'texto' => ['abc', 'El ID de categoría debe ser numérico'],
            'cero' => [0, 'El ID de categoría debe ser positivo'],
            'negativo' => [-1, 'El ID de categoría debe ser positivo'],
        ];
    }

    public function test_validar_nombre_ok(): void
    {
        $resultado = CategoriaValidator::validarNombre('Deportes');

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public function test_validar_nombre_con_letras_unicode_y_simbolos_permitidos_ok(): void
    {
        $resultado = CategoriaValidator::validarNombre('Ñandú 1-2 & Co');

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    #[DataProvider('nombresInvalidos')]
    public function test_validar_nombre_falla(
        ?string $nombre,
        string $errorEsperado
    ): void {
        $resultado = CategoriaValidator::validarNombre($nombre);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function nombresInvalidos(): array
    {
        return [
            'null' => [null, 'El nombre de la categoría es requerido'],
            'vacio' => ['', 'El nombre de la categoría es requerido'],
            'muy_corto' => ['ab', 'El nombre debe tener al menos 3 caracteres'],
            'muy_largo' => [
                str_repeat('a', 51),
                'El nombre no puede exceder los 50 caracteres',
            ],
            'caracteres_no_permitidos' => [
                'Deporte@1',
                'El nombre solo puede contener letras, números, espacios, guiones y &',
            ],
        ];
    }

    public function test_validar_categoria_ok(): void
    {
        $resultado = CategoriaValidator::validarCategoria([
            'nombre' => 'Deportes',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_categoria_ok_con_id_requerido(): void
    {
        $resultado = CategoriaValidator::validarCategoria(
            ['id' => 3, 'nombre' => 'Deportes'],
            true
        );

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_categoria_sin_id_cuando_se_requiere_falla(): void
    {
        $resultado = CategoriaValidator::validarCategoria(
            ['nombre' => 'Deportes'],
            true
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['id' => 'El ID de categoría es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_categoria_sin_id_cuando_no_se_requiere_es_ok(): void
    {
        $resultado = CategoriaValidator::validarCategoria(
            ['id' => null, 'nombre' => 'Deportes'],
            false
        );

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_categoria_nombre_invalido_falla(): void
    {
        $resultado = CategoriaValidator::validarCategoria([
            'nombre' => 'ab',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['nombre' => 'El nombre debe tener al menos 3 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_categoria_con_varios_campos_invalidos_acumula_errores(): void
    {
        $resultado = CategoriaValidator::validarCategoria(
            ['id' => 'x', 'nombre' => ''],
            true
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'id' => 'El ID de categoría debe ser numérico',
                'nombre' => 'El nombre de la categoría es requerido',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_solo_id_categoria_ok(): void
    {
        $resultado = CategoriaValidator::validarSoloIdCategoria(4);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsInvalidosParaRuta')]
    public function test_validar_solo_id_categoria_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = CategoriaValidator::validarSoloIdCategoria($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }

    public static function idsInvalidosParaRuta(): array
    {
        return [
            'null' => [null, 'El ID de categoría es requerido'],
            'vacio' => ['', 'El ID de categoría es requerido'],
            'texto' => ['abc', 'El ID de categoría debe ser numérico'],
            'cero' => [0, 'El ID de categoría debe ser positivo'],
        ];
    }
}
