<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\UsuarioValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UsuarioValidatorTest extends TestCase
{
    #[DataProvider('idsUsuarioInvalidos')]
    public function test_validar_id_usuario_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = UsuarioValidator::validarIdUsuario($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function idsUsuarioInvalidos(): array
    {
        return [
            'null' => [null, 'El ID de usuario es requerido'],
            'vacio' => ['', 'El ID de usuario es requerido'],
            'texto' => ['abc', 'El ID de usuario debe ser numérico'],
            'cero' => [0, 'El ID de usuario debe ser positivo'],
            'negativo' => [-1, 'El ID de usuario debe ser positivo'],
        ];
    }

    #[DataProvider('idsUsuarioValidos')]
    public function test_validar_id_usuario_ok(mixed $id): void
    {
        $resultado = UsuarioValidator::validarIdUsuario($id);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function idsUsuarioValidos(): array
    {
        return [
            'entero' => [8],
            'cadena_numerica' => ['8'],
        ];
    }

    #[DataProvider('nombresInvalidos')]
    public function test_validar_nombre_falla(
        ?string $nombre,
        string $errorEsperado
    ): void {
        $resultado = UsuarioValidator::validarNombre($nombre);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function nombresInvalidos(): array
    {
        return [
            'null' => [null, 'El nombre es requerido'],
            'vacio' => ['', 'El nombre es requerido'],
            'un_caracter' => [
                'a',
                'El nombre debe tener al menos 2 caracteres',
            ],
            'espacios_solamente' => [
                '  ',
                'El nombre debe tener al menos 2 caracteres',
            ],
            'largo' => [
                str_repeat('a', 51),
                'El nombre no puede exceder los 50 caracteres',
            ],
            'con_numeros' => [
                'Juan1',
                'El nombre solo puede contener letras y espacios',
            ],
            'profanidad' => [
                'puta',
                'El nombre contiene lenguaje inapropiado',
            ],
        ];
    }

    #[DataProvider('nombresValidos')]
    public function test_validar_nombre_ok(string $nombre): void
    {
        $resultado = UsuarioValidator::validarNombre($nombre);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function nombresValidos(): array
    {
        return [
            'simple' => ['Ana'],
            'con_espacios' => ['Ana Lucía'],
            'con_espacios_al_costado' => ['  Ana  '],
            'maximo' => [str_repeat('a', 50)],
        ];
    }

    #[DataProvider('apellidosInvalidos')]
    public function test_validar_apellido_falla(
        ?string $apellido,
        string $errorEsperado
    ): void {
        $resultado = UsuarioValidator::validarApellido($apellido);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function apellidosInvalidos(): array
    {
        return [
            'null' => [null, 'El apellido es requerido'],
            'vacio' => ['', 'El apellido es requerido'],
            'un_caracter' => [
                'b',
                'El apellido debe tener al menos 2 caracteres',
            ],
            'largo' => [
                str_repeat('a', 51),
                'El apellido no puede exceder los 50 caracteres',
            ],
            'con_signos' => [
                'García!',
                'El apellido solo puede contener letras y espacios',
            ],
            'profanidad' => [
                'puta',
                'El apellido contiene lenguaje inapropiado',
            ],
        ];
    }

    #[DataProvider('apellidosValidos')]
    public function test_validar_apellido_ok(string $apellido): void
    {
        $resultado = UsuarioValidator::validarApellido($apellido);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function apellidosValidos(): array
    {
        return [
            'simple' => ['García'],
            'compuesto' => ['García López'],
            'con_espacios_al_costado' => ['  García  '],
        ];
    }

    #[DataProvider('emailsInvalidos')]
    public function test_validar_email_falla(
        ?string $email,
        string $errorEsperado
    ): void {
        $resultado = UsuarioValidator::validarEmail($email);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function emailsInvalidos(): array
    {
        return [
            'null' => [null, 'El email es requerido'],
            'vacio' => ['', 'El email es requerido'],
            'sin_arroba' => [
                'usuario.mail.com',
                'El email no es válido',
            ],
            'sin_dominio' => [
                'usuario@',
                'El email no es válido',
            ],
            'largo' => [
                str_repeat('a', 92) . '@mail.com',
                'El email no puede exceder los 100 caracteres',
            ],
        ];
    }

    #[DataProvider('emailsValidos')]
    public function test_validar_email_ok(string $email): void
    {
        $resultado = UsuarioValidator::validarEmail($email);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function emailsValidos(): array
    {
        return [
            'simple' => ['ana@mail.com'],
            'con_puntos' => ['ana.garcia@mail.com.ar'],
        ];
    }

    #[DataProvider('telefonosInvalidos')]
    public function test_validar_telefono_falla(
        ?string $telefono,
        string $errorEsperado
    ): void {
        $resultado = UsuarioValidator::validarTelefono($telefono);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function telefonosInvalidos(): array
    {
        return [
            'null' => [null, 'El teléfono es requerido'],
            'vacio' => ['', 'El teléfono es requerido'],
            'muy_corto' => [
                '12345',
                'El teléfono debe tener al menos 6 dígitos',
            ],
            'con_letras_y_pocos_digitos' => [
                'abc123',
                'El teléfono debe tener al menos 6 dígitos',
            ],
            'muy_largo' => [
                str_repeat('1', 16),
                'El teléfono no puede exceder los 15 dígitos',
            ],
        ];
    }

    #[DataProvider('telefonosValidos')]
    public function test_validar_telefono_ok(string $telefono): void
    {
        $resultado = UsuarioValidator::validarTelefono($telefono);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function telefonosValidos(): array
    {
        return [
            'simple' => ['1122334455'],
            'con_formato' => ['+54 11 2233-4455'],
            'con_parentesis' => ['(011) 15-22334455'],
        ];
    }

    #[DataProvider('domiciliosInvalidos')]
    public function test_validar_domicilio_falla(
        ?string $domicilio,
        string $errorEsperado
    ): void {
        $resultado = UsuarioValidator::validarDomicilio($domicilio);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function domiciliosInvalidos(): array
    {
        return [
            'null' => [null, 'El domicilio es requerido'],
            'vacio' => ['', 'El domicilio es requerido'],
            'corto' => [
                'av 1',
                'El domicilio debe tener al menos 5 caracteres',
            ],
            'largo' => [
                str_repeat('a', 101),
                'El domicilio no puede exceder los 100 caracteres',
            ],
            'profanidad' => [
                'puta domicilio',
                'El domicilio contiene lenguaje inapropiado',
            ],
        ];
    }

    #[DataProvider('domiciliosValidos')]
    public function test_validar_domicilio_ok(string $domicilio): void
    {
        $resultado = UsuarioValidator::validarDomicilio($domicilio);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function domiciliosValidos(): array
    {
        return [
            'simple' => ['Av. Siempreviva 742'],
            'con_espacios_al_costado' => ['  Av. Siempreviva 742  '],
        ];
    }

    #[DataProvider('contrasenasInvalidas')]
    public function test_validar_contrasena_falla(
        ?string $contrasena,
        string $errorEsperado
    ): void {
        $resultado = UsuarioValidator::validarContrasena($contrasena);

        $this->assertFalse($resultado['success']);
        $this->assertSame($errorEsperado, $resultado['error']);
    }

    public static function contrasenasInvalidas(): array
    {
        return [
            'null' => [null, 'La contraseña es requerida'],
            'vacia' => ['', 'La contraseña es requerida'],
            'corta' => [
                'abcde',
                'La contraseña debe tener al menos 6 caracteres',
            ],
            'larga' => [
                str_repeat('a', 256),
                'La contraseña no puede exceder los 255 caracteres',
            ],
        ];
    }

    #[DataProvider('contrasenasValidas')]
    public function test_validar_contrasena_ok(string $contrasena): void
    {
        $resultado = UsuarioValidator::validarContrasena($contrasena);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['error']);
    }

    public static function contrasenasValidas(): array
    {
        return [
            'minima' => ['abc123'],
            'larga' => [str_repeat('a', 255)],
        ];
    }

    public function test_validar_registro_ok(): void
    {
        $resultado = UsuarioValidator::validarRegistro([
            'nombre' => 'Ana',
            'apellido' => 'García',
            'email' => 'ana@mail.com',
            'telefono' => '1122334455',
            'domicilio' => 'Av. Siempreviva 742',
            'contrasena' => 'secreta1',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame([], $resultado['errors']);
    }

    public function test_validar_registro_vacio_acumula_todos_los_errores(): void
    {
        $resultado = UsuarioValidator::validarRegistro([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'nombre' => 'El nombre es requerido',
                'apellido' => 'El apellido es requerido',
                'email' => 'El email es requerido',
                'telefono' => 'El teléfono es requerido',
                'domicilio' => 'El domicilio es requerido',
                'contrasena' => 'La contraseña es requerida',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_registro_con_campos_invalidos_acumula_errores(): void
    {
        $resultado = UsuarioValidator::validarRegistro([
            'nombre' => 'a',
            'apellido' => 'García',
            'email' => 'no-es-email',
            'telefono' => '1122334455',
            'domicilio' => 'Av. Siempreviva 742',
            'contrasena' => 'secreta1',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'nombre' => 'El nombre debe tener al menos 2 caracteres',
                'email' => 'El email no es válido',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_actualizacion_parcial_vacia_es_ok(): void
    {
        $resultado = UsuarioValidator::validarActualizacionParcial([]);

        $this->assertTrue($resultado['success']);
        $this->assertSame([], $resultado['errors']);
    }

    public function test_validar_actualizacion_parcial_ok(): void
    {
        $resultado = UsuarioValidator::validarActualizacionParcial([
            'nombre' => 'Ana Lucía',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame([], $resultado['errors']);
    }

    public function test_validar_actualizacion_parcial_invalida_solo_el_campo_presente(): void
    {
        $resultado = UsuarioValidator::validarActualizacionParcial([
            'email' => 'mal@',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['email' => 'El email no es válido'],
            $resultado['errors']
        );
    }

    public function test_validar_actualizacion_parcial_con_varios_campos_invalidos(): void
    {
        $resultado = UsuarioValidator::validarActualizacionParcial([
            'nombre' => 'a',
            'contrasena' => 'abc',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'nombre' => 'El nombre debe tener al menos 2 caracteres',
                'contrasena' =>
                    'La contraseña debe tener al menos 6 caracteres',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_solo_id_usuario_ok(): void
    {
        $resultado = UsuarioValidator::validarSoloIdUsuario(4);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsUsuarioInvalidos')]
    public function test_validar_solo_id_usuario_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = UsuarioValidator::validarSoloIdUsuario($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }

    public function test_validar_email_login_usuario_ok(): void
    {
        $resultado = UsuarioValidator::validarEmailLoginUsuario(
            'ana@mail.com'
        );

        $this->assertTrue($resultado['success']);
        $this->assertSame('Email válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('emailsInvalidos')]
    public function test_validar_email_login_usuario_falla(
        ?string $email,
        string $errorEsperado
    ): void {
        $resultado = UsuarioValidator::validarEmailLoginUsuario($email);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Email inválido', $resultado['message']);
        $this->assertSame(
            ['email' => $errorEsperado],
            $resultado['errors']
        );
    }
}
