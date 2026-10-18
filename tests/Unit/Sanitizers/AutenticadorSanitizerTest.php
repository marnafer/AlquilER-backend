<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\AutenticadorSanitizer;
use Tests\TestCase;

class AutenticadorSanitizerTest extends TestCase
{
    public function test_sanitizar_login_trims_string_email(): void
    {
        $resultado = AutenticadorSanitizer::sanitizarLogin([
            'email' => '  usuario@example.com ',
            'contrasena' => 'secreto',
        ]);

        $this->assertSame('usuario@example.com', $resultado['email']);
        $this->assertSame('secreto', $resultado['contrasena']);
    }

    public function test_sanitizar_login_keeps_non_string_email(): void
    {
        $resultado = AutenticadorSanitizer::sanitizarLogin(['email' => 123]);

        $this->assertSame(123, $resultado['email']);
    }

    public function test_sanitizar_login_with_missing_fields(): void
    {
        $resultado = AutenticadorSanitizer::sanitizarLogin([]);

        $this->assertNull($resultado['email']);
        $this->assertNull($resultado['contrasena']);
    }

    public function test_sanitizar_refresh_trims_string_token(): void
    {
        $resultado = AutenticadorSanitizer::sanitizarRefresh([
            'refresh_token' => '  token-123  ',
        ]);

        $this->assertSame('token-123', $resultado['refresh_token']);
    }

    public function test_sanitizar_refresh_keeps_non_string_token(): void
    {
        $resultado = AutenticadorSanitizer::sanitizarRefresh([
            'refresh_token' => 456,
        ]);

        $this->assertSame(456, $resultado['refresh_token']);
    }

    public function test_sanitizar_refresh_with_missing_token(): void
    {
        $resultado = AutenticadorSanitizer::sanitizarRefresh([]);

        $this->assertNull($resultado['refresh_token']);
    }

    public function test_sanitizar_logout_trims_token(): void
    {
        $resultado = AutenticadorSanitizer::sanitizarLogout([
            'refresh_token' => '  token-abc  ',
        ]);

        $this->assertSame('token-abc', $resultado['refresh_token']);
    }

    public function test_sanitizar_logout_without_token_returns_null(): void
    {
        $resultado = AutenticadorSanitizer::sanitizarLogout([]);

        $this->assertNull($resultado['refresh_token']);
    }
}
