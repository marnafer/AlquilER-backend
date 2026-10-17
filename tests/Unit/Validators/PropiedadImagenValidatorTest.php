<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\PropiedadImagenValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PropiedadImagenValidatorTest extends TestCase
{
    public function test_validar_solo_id_imagen_nulo_falla(): void
    {
        $resultado = PropiedadImagenValidator::validarSoloIdPropiedadImagen(
            null
        );

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['id' => 'El ID de imagen debe ser un entero positivo'],
            $resultado['errors']
        );
    }

    #[DataProvider('valoresNoNulos')]
    public function test_validar_solo_id_imagen_no_nulo_es_ok(
        mixed $id
    ): void {
        $resultado = PropiedadImagenValidator::validarSoloIdPropiedadImagen(
            $id
        );

        $this->assertTrue($resultado['success']);
        $this->assertSame('OK', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public static function valoresNoNulos(): array
    {
        return [
            'entero' => [1],
            'cero' => [0],
            'texto' => ['cualquier-valor'],
            'vacio' => [''],
            'booleano' => [false],
        ];
    }

    public function test_validar_crear_imagen_ok(): void
    {
        $resultado = PropiedadImagenValidator::validarCrearPropiedadImagen([
            'propiedad_id' => 4,
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('OK', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_crear_imagen_sin_propiedad_id_falla(): void
    {
        $resultado = PropiedadImagenValidator::validarCrearPropiedadImagen([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['propiedad_id' => 'El ID de propiedad es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_crear_imagen_con_propiedad_id_nulo_falla(): void
    {
        $resultado = PropiedadImagenValidator::validarCrearPropiedadImagen([
            'propiedad_id' => null,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['propiedad_id' => 'El ID de propiedad es requerido'],
            $resultado['errors']
        );
    }
}
