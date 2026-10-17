<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\AutenticadorValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AutenticadorValidatorTest extends TestCase
{
    public function test_validar_login_ok(): void
    {
        $resultado = AutenticadorValidator::validarLogin([
            'email' => 'juan@correo.com',
            'contrasena' => 'secreta123',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame([], $resultado['errors']);
    }

    public function test_validar_login_email_null_falla(): void
    {
        $resultado = AutenticadorValidator::validarLogin([
            'email' => null,
            'contrasena' => 'secreta123',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['email' => ['El email es obligatorio']],
            $resultado['errors']
        );
    }

    #[DataProvider('emailsQueNoSonCadena')]
    public function test_validar_login_email_no_es_cadena_falla(
        mixed $email
    ): void {
        $resultado = AutenticadorValidator::validarLogin([
            'email' => $email,
            'contrasena' => 'secreta123',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['email' => ['El email debe ser una cadena de texto']],
            $resultado['errors']
        );
    }

    public static function emailsQueNoSonCadena(): array
    {
        return [
            'entero' => [123],
            'booleano' => [true],
            'arreglo' => [['juan@correo.com']],
        ];
    }

    public function test_validar_login_contrasena_null_falla(): void
    {
        $resultado = AutenticadorValidator::validarLogin([
            'email' => 'juan@correo.com',
            'contrasena' => null,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['contrasena' => ['La contraseña es obligatoria']],
            $resultado['errors']
        );
    }

    #[DataProvider('contrasenasQueNoSonCadena')]
    public function test_validar_login_contrasena_no_es_cadena_falla(
        mixed $contrasena
    ): void {
        $resultado = AutenticadorValidator::validarLogin([
            'email' => 'juan@correo.com',
            'contrasena' => $contrasena,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['contrasena' => ['La contraseña debe ser una cadena de texto']],
            $resultado['errors']
        );
    }

    public static function contrasenasQueNoSonCadena(): array
    {
        return [
            'entero' => [123456],
            'arreglo' => [['secreta123']],
        ];
    }

    public function test_validar_login_con_dos_campos_invalidos_acumula_ambos_errores(): void
    {
        $resultado = AutenticadorValidator::validarLogin([
            'email' => null,
            'contrasena' => null,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'email' => ['El email es obligatorio'],
                'contrasena' => ['La contraseña es obligatoria'],
            ],
            $resultado['errors']
        );
    }

    public function test_validar_refresh_ok(): void
    {
        $resultado = AutenticadorValidator::validarRefresh([
            'refresh_token' => 'token-123',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame([], $resultado['errors']);
    }

    public function test_validar_refresh_token_null_falla(): void
    {
        $resultado = AutenticadorValidator::validarRefresh([
            'refresh_token' => null,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['refresh_token' => ['El refresh token es obligatorio']],
            $resultado['errors']
        );
    }

    #[DataProvider('tokensQueNoSonCadena')]
    public function test_validar_refresh_token_no_es_cadena_falla(
        mixed $token
    ): void {
        $resultado = AutenticadorValidator::validarRefresh([
            'refresh_token' => $token,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['refresh_token' => ['El refresh token debe ser una cadena de texto']],
            $resultado['errors']
        );
    }

    public static function tokensQueNoSonCadena(): array
    {
        return [
            'entero' => [42],
            'arreglo' => [['token-123']],
        ];
    }
}
