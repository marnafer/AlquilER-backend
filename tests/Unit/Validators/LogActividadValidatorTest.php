<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\LogActividadValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LogActividadValidatorTest extends TestCase
{
    public function test_validar_ok(): void
    {
        $resultado = LogActividadValidator::validar([
            'usuario_id' => 7,
            'accion' => 'login',
            'ip_address' => '192.168.1.10',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_ok_sin_ip_address(): void
    {
        $resultado = LogActividadValidator::validar([
            'usuario_id' => 7,
            'accion' => 'logout',
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('ipsOmitidas')]
    public function test_validar_omite_ip_address_vacia_o_nula(
        mixed $ip
    ): void {
        $resultado = LogActividadValidator::validar([
            'usuario_id' => 7,
            'accion' => 'login',
            'ip_address' => $ip,
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public static function ipsOmitidas(): array
    {
        return [
            'null' => [null],
            'vacia' => [''],
        ];
    }

    public function test_validar_sin_usuario_id_falla(): void
    {
        $resultado = LogActividadValidator::validar([
            'accion' => 'login',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            ['usuario_id' => 'El ID de usuario es requerido. Debe ser un entero positivo.'],
            $resultado['errors']
        );
    }

    public function test_validar_sin_accion_falla(): void
    {
        $resultado = LogActividadValidator::validar([
            'usuario_id' => 7,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['accion' => 'La acción es obligatoria'],
            $resultado['errors']
        );
    }

    public function test_validar_con_accion_corta_falla(): void
    {
        $resultado = LogActividadValidator::validar([
            'usuario_id' => 7,
            'accion' => 'ab',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['accion' => 'La acción debe tener al menos 3 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_con_accion_larga_falla(): void
    {
        $resultado = LogActividadValidator::validar([
            'usuario_id' => 7,
            'accion' => str_repeat('a', 256),
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['accion' => 'La acción no puede superar los 255 caracteres'],
            $resultado['errors']
        );
    }

    public function test_validar_con_ip_invalida_falla(): void
    {
        $resultado = LogActividadValidator::validar([
            'usuario_id' => 7,
            'accion' => 'login',
            'ip_address' => 'no-es-una-ip',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            ['ip_address' => 'La IP no es válida'],
            $resultado['errors']
        );
    }

    public function test_validar_con_varios_campos_invalidos_acumula_errores(): void
    {
        $resultado = LogActividadValidator::validar([
            'usuario_id' => null,
            'accion' => 'x',
            'ip_address' => 'no-es-una-ip',
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'usuario_id' => 'El ID de usuario es requerido. Debe ser un entero positivo.',
                'accion' => 'La acción debe tener al menos 3 caracteres',
                'ip_address' => 'La IP no es válida',
            ],
            $resultado['errors']
        );
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            LogActividadValidator::validarId($id)
        );
    }

    public static function idsInvalidos(): array
    {
        return [
            'null' => [null, 'El ID es requerido. Debe ser un entero positivo.'],
            'vacio' => ['', 'El ID es requerido. Debe ser un entero positivo.'],
            'texto' => ['abc', 'El ID debe ser numérico'],
            'cero' => [0, 'El ID debe ser mayor a 0'],
            'negativo' => [-4, 'El ID debe ser mayor a 0'],
        ];
    }

    #[DataProvider('idsValidos')]
    public function test_validar_id_ok(mixed $id): void
    {
        $this->assertNull(LogActividadValidator::validarId($id));
    }

    public static function idsValidos(): array
    {
        return [
            'entero' => [3],
            'cadena_numerica' => ['3'],
        ];
    }

    #[DataProvider('usuariosIdsInvalidos')]
    public function test_validar_usuario_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            LogActividadValidator::validarUsuarioId($id)
        );
    }

    public static function usuariosIdsInvalidos(): array
    {
        return [
            'null' => [
                null,
                'El ID de usuario es requerido. Debe ser un entero positivo.',
            ],
            'vacio' => [
                '',
                'El ID de usuario es requerido. Debe ser un entero positivo.',
            ],
            'texto' => ['abc', 'El ID de usuario debe ser un número'],
            'cero' => [0, 'El ID de usuario debe ser un número positivo'],
            'negativo' => [-1, 'El ID de usuario debe ser un número positivo'],
            'decimal' => [1.5, 'El ID de usuario debe ser un número entero'],
        ];
    }

    #[DataProvider('idsValidos')]
    public function test_validar_usuario_id_ok(mixed $id): void
    {
        $this->assertNull(LogActividadValidator::validarUsuarioId($id));
    }

    #[DataProvider('accionesInvalidas')]
    public function test_validar_accion_falla(
        mixed $accion,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            LogActividadValidator::validarAccion($accion)
        );
    }

    public static function accionesInvalidas(): array
    {
        return [
            'null' => [null, 'La acción es obligatoria'],
            'vacia' => ['', 'La acción es obligatoria'],
            'corta' => ['ab', 'La acción debe tener al menos 3 caracteres'],
            'con_espacios' => ['  ab  ', 'La acción debe tener al menos 3 caracteres'],
            'larga' => [
                str_repeat('a', 256),
                'La acción no puede superar los 255 caracteres',
            ],
        ];
    }

    #[DataProvider('accionesValidas')]
    public function test_validar_accion_ok(mixed $accion): void
    {
        $this->assertNull(LogActividadValidator::validarAccion($accion));
    }

    public static function accionesValidas(): array
    {
        return [
            'texto' => ['login'],
            'con_espacios' => ['  creó una propiedad  '],
        ];
    }

    #[DataProvider('ipsValidas')]
    public function test_validar_ip_ok(mixed $ip): void
    {
        $this->assertNull(LogActividadValidator::validarIp($ip));
    }

    public static function ipsValidas(): array
    {
        return [
            'ipv4' => ['10.0.0.1'],
            'ipv6' => ['2001:db8::1'],
            'null' => [null],
            'vacia' => [''],
        ];
    }

    public function test_validar_ip_invalida_falla(): void
    {
        $this->assertSame(
            'La IP no es válida',
            LogActividadValidator::validarIp('999.999.999.999')
        );
    }

    public function test_validar_solo_id_ok(): void
    {
        $resultado = LogActividadValidator::validarSoloId(12);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_solo_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = LogActividadValidator::validarSoloId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }
}
