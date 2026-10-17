<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\RecuperarContrasenaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RecuperarContrasenaValidatorTest extends TestCase
{
    public function test_validar_solicitud_ok(): void
    {
        $resultado = RecuperarContrasenaValidator::validarSolicitud([
            'email' => 'juan@correo.com',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_solicitud_sin_email_falla(): void
    {
        $resultado = RecuperarContrasenaValidator::validarSolicitud([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['email' => 'El email es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_solicitud_con_email_invalido_falla(): void
    {
        $resultado = RecuperarContrasenaValidator::validarSolicitud([
            'email' => 'no-es-un-email',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['email' => 'El email no es válido'],
            $resultado['errors']
        );
    }

    public function test_validar_solicitud_con_email_largo_falla(): void
    {
        $resultado = RecuperarContrasenaValidator::validarSolicitud([
            'email' => str_repeat('a', 95) . '@correo.com',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['email' => 'El email no puede exceder los 100 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_restablecer_ok(): void
    {
        $resultado = RecuperarContrasenaValidator::validarRestablecer([
            'email' => 'juan@correo.com',
            'token' => 'token-abc123',
            'contrasena' => 'nuevaclave1',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_restablecer_sin_token_falla(): void
    {
        $resultado = RecuperarContrasenaValidator::validarRestablecer([
            'email' => 'juan@correo.com',
            'contrasena' => 'nuevaclave1',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['token' => 'El token es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_restablecer_con_token_en_blanco_falla(): void
    {
        $resultado = RecuperarContrasenaValidator::validarRestablecer([
            'email' => 'juan@correo.com',
            'token' => '   ',
            'contrasena' => 'nuevaclave1',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['token' => 'El token es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_restablecer_con_contrasena_corta_falla(): void
    {
        $resultado = RecuperarContrasenaValidator::validarRestablecer([
            'email' => 'juan@correo.com',
            'token' => 'token-abc123',
            'contrasena' => 'abcde',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['contrasena' => 'La contraseña debe tener al menos 6 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_restablecer_sin_contrasena_falla(): void
    {
        $resultado = RecuperarContrasenaValidator::validarRestablecer([
            'email' => 'juan@correo.com',
            'token' => 'token-abc123',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['contrasena' => 'La contraseña es requerida'],
            $resultado['errors']
        );
    }

    #[DataProvider('emailsInvalidos')]
    public function test_validar_restablecer_con_email_invalido_falla(
        ?string $email
    ): void {
        $resultado = RecuperarContrasenaValidator::validarRestablecer([
            'email' => $email,
            'token' => 'token-abc123',
            'contrasena' => 'nuevaclave1',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['email' => $email === null || $email === ''
                ? 'El email es requerido'
                : 'El email no es válido'],
            $resultado['errors']
        );
    }

    public static function emailsInvalidos(): array
    {
        return [
            'null' => [null],
            'vacio' => [''],
            'sin_arroba' => ['juan.correo.com'],
        ];
    }

    public function test_validar_restablecer_con_todos_los_campos_invalidos_acumula_errores(): void
    {
        $resultado = RecuperarContrasenaValidator::validarRestablecer([
            'email' => 'no-es-un-email',
            'contrasena' => 'abc',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'email' => 'El email no es válido',
                'token' => 'El token es requerido',
                'contrasena' => 'La contraseña debe tener al menos 6 caracteres',
            ],
            $resultado['errors']
        );
    }
}
