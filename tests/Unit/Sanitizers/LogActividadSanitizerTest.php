<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\LogActividadSanitizer;
use Tests\TestCase;

class LogActividadSanitizerTest extends TestCase
{
    public function test_sanitizar_id_null_and_empty(): void
    {
        $this->assertNull(LogActividadSanitizer::sanitizarId(null));
        $this->assertNull(LogActividadSanitizer::sanitizarId(''));
    }

    public function test_sanitizar_id_valid_and_invalid(): void
    {
        $this->assertSame(12, LogActividadSanitizer::sanitizarId('12'));
        $this->assertNull(LogActividadSanitizer::sanitizarId('abc'));
        $this->assertNull(LogActividadSanitizer::sanitizarId('0'));
    }

    public function test_sanitizar_usuario_id_null_and_empty(): void
    {
        $this->assertNull(LogActividadSanitizer::sanitizarUsuarioId(null));
        $this->assertNull(LogActividadSanitizer::sanitizarUsuarioId(''));
    }

    public function test_sanitizar_usuario_id_valid_and_invalid(): void
    {
        $this->assertSame(3, LogActividadSanitizer::sanitizarUsuarioId('3'));
        $this->assertNull(LogActividadSanitizer::sanitizarUsuarioId('abc'));
    }

    public function test_sanitizar_accion(): void
    {
        $this->assertSame(
            'crear propiedad',
            LogActividadSanitizer::sanitizarAccion('  crear   <b>propiedad</b> ')
        );
        $this->assertNull(LogActividadSanitizer::sanitizarAccion(null));
        $this->assertNull(LogActividadSanitizer::sanitizarAccion(''));
        $this->assertSame(255, mb_strlen(LogActividadSanitizer::sanitizarAccion(str_repeat('a', 300))));
    }

    public function test_sanitizar_ip_valid(): void
    {
        $this->assertSame('192.168.1.1', LogActividadSanitizer::sanitizarIp(' 192.168.1.1 '));
        $this->assertSame('::1', LogActividadSanitizer::sanitizarIp('::1'));
    }

    public function test_sanitizar_ip_invalid_returns_null(): void
    {
        $this->assertNull(LogActividadSanitizer::sanitizarIp('no-es-ip'));
    }

    public function test_sanitizar_ip_null_and_empty(): void
    {
        $this->assertNull(LogActividadSanitizer::sanitizarIp(null));
        $this->assertNull(LogActividadSanitizer::sanitizarIp(''));
    }

    public function test_sanitizar_crear(): void
    {
        $resultado = LogActividadSanitizer::sanitizarCrear([
            'usuario_id' => '5',
            'accion' => '  login ',
            'ip_address' => '10.0.0.1',
        ]);

        $this->assertSame(5, $resultado['usuario_id']);
        $this->assertSame('login', $resultado['accion']);
        $this->assertSame('10.0.0.1', $resultado['ip_address']);
    }

    public function test_sanitizar_crear_with_missing_fields(): void
    {
        $resultado = LogActividadSanitizer::sanitizarCrear([]);

        $this->assertNull($resultado['usuario_id']);
        $this->assertNull($resultado['accion']);
        $this->assertNull($resultado['ip_address']);
    }
}
