<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\UsuarioValidator;
use PHPUnit\Framework\TestCase;

final class UsuarioValidatorCoverageTest extends TestCase
{
    public function test_validar_contrasena_rechaza_sin_mayuscula(): void
    {
        $resultado = UsuarioValidator::validarContrasena('secret12');

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            'La contraseña debe contener al menos una mayúscula',
            $resultado['error']
        );
    }

    public function test_validar_contrasena_rechaza_sin_numero(): void
    {
        $resultado = UsuarioValidator::validarContrasena('Secretaa');

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            'La contraseña debe contener al menos un número',
            $resultado['error']
        );
    }
}