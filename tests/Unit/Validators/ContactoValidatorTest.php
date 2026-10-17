<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\ContactoValidator;
use PHPUnit\Framework\TestCase;

final class ContactoValidatorTest extends TestCase
{
    public function test_validar_ok(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 'Juan Pérez',
            'email' => 'juan@correo.com',
            'asunto' => 'Consulta sobre la propiedad',
            'mensaje' => 'Hola, me gustaría coordinar una visita.',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_sin_nombre_falla(): void
    {
        $resultado = ContactoValidator::validar([
            'email' => 'juan@correo.com',
            'asunto' => 'Consulta sobre la propiedad',
            'mensaje' => 'Hola, me gustaría coordinar una visita.',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['nombre' => 'El nombre es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_con_nombre_corto_falla(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 'A',
            'email' => 'juan@correo.com',
            'asunto' => 'Consulta sobre la propiedad',
            'mensaje' => 'Hola, me gustaría coordinar una visita.',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['nombre' => 'El nombre debe tener al menos 2 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_con_nombre_con_caracteres_invalidos_falla(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 123,
            'email' => 'juan@correo.com',
            'asunto' => 'Consulta sobre la propiedad',
            'mensaje' => 'Hola, me gustaría coordinar una visita.',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['nombre' => 'El nombre solo puede contener letras y espacios'],
            $resultado['errors']
        );
    }

    public function test_validar_sin_email_falla(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 'Juan Pérez',
            'asunto' => 'Consulta sobre la propiedad',
            'mensaje' => 'Hola, me gustaría coordinar una visita.',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['email' => 'El email es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_con_email_invalido_falla(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 'Juan Pérez',
            'email' => 'no-es-un-email',
            'asunto' => 'Consulta sobre la propiedad',
            'mensaje' => 'Hola, me gustaría coordinar una visita.',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['email' => 'El email no es válido'],
            $resultado['errors']
        );
    }

    public function test_validar_con_email_largo_falla(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 'Juan Pérez',
            'email' => str_repeat('a', 95) . '@correo.com',
            'asunto' => 'Consulta sobre la propiedad',
            'mensaje' => 'Hola, me gustaría coordinar una visita.',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['email' => 'El email no puede exceder los 100 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_sin_asunto_falla(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 'Juan Pérez',
            'email' => 'juan@correo.com',
            'mensaje' => 'Hola, me gustaría coordinar una visita.',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['asunto' => 'El asunto es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_con_asunto_en_blanco_falla(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 'Juan Pérez',
            'email' => 'juan@correo.com',
            'asunto' => '   ',
            'mensaje' => 'Hola, me gustaría coordinar una visita.',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['asunto' => 'El asunto es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_con_asunto_corto_falla(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 'Juan Pérez',
            'email' => 'juan@correo.com',
            'asunto' => 'ab',
            'mensaje' => 'Hola, me gustaría coordinar una visita.',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['asunto' => 'El asunto debe tener al menos 3 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_sin_mensaje_falla(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 'Juan Pérez',
            'email' => 'juan@correo.com',
            'asunto' => 'Consulta sobre la propiedad',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['mensaje' => 'El mensaje es requerido'],
            $resultado['errors']
        );
    }

    public function test_validar_con_mensaje_corto_falla(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 'Juan Pérez',
            'email' => 'juan@correo.com',
            'asunto' => 'Consulta sobre la propiedad',
            'mensaje' => '  hola  ',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['mensaje' => 'El mensaje debe tener al menos 10 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_con_varios_campos_invalidos_acumula_errores(): void
    {
        $resultado = ContactoValidator::validar([
            'nombre' => 'A',
            'email' => 'no-es-un-email',
            'asunto' => 'ab',
            'mensaje' => 'corto',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'nombre' => 'El nombre debe tener al menos 2 caracteres',
                'email' => 'El email no es válido',
                'asunto' => 'El asunto debe tener al menos 3 caracteres',
                'mensaje' => 'El mensaje debe tener al menos 10 caracteres',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_sin_ningun_campo_acumula_todos_los_errores(): void
    {
        $resultado = ContactoValidator::validar([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'nombre' => 'El nombre es requerido',
                'email' => 'El email es requerido',
                'asunto' => 'El asunto es requerido',
                'mensaje' => 'El mensaje es requerido',
            ],
            $resultado['errors']
        );
    }
}
