<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\JwtProvider;
use App\Models\Usuario;
use PHPUnit\Framework\TestCase;

final class JwtProviderTest extends TestCase
{
    private JwtProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new JwtProvider();

        // Si tus constantes globales no se cargan automáticamente en el entorno de testing, 
        // defínelas aquí para evitar errores.
        if (!defined('JWT_KEY')) {
            define('JWT_KEY', 'clave-secreta-de-prueba-minimo-32-caracteres');
        }
        if (!defined('JWT_ALGORITHM')) {
            define('JWT_ALGORITHM', 'HS256');
        }
    }

    public function test_generate_access_token_devuelve_un_jwt_valido(): void
    {
        $usuario = new Usuario();
        $usuario->id = 1;
        $usuario->email = 'test@example.com';
        $usuario->rol_id = 2;

        $token = $this->provider->generateAccessToken($usuario);

        // Un JWT siempre tiene 3 partes separadas por un punto (header.payload.signature)
        $partes = explode('.', $token);
        
        $this->assertIsString($token);
        $this->assertCount(3, $partes);
    }

    public function test_generate_refresh_token_devuelve_una_cadena_aleatoria_de_80_caracteres(): void
    {
        $token1 = $this->provider->generateRefreshToken();
        $token2 = $this->provider->generateRefreshToken();

        $this->assertIsString($token1);
        $this->assertEquals(80, strlen($token1));
        // Verificamos que sea hexadecimal (bin2hex)
        $this->assertMatchesRegularExpression('/^[a-f0-9]{80}$/', $token1);
        // Verificamos que no genere el mismo token dos veces
        $this->assertNotEquals($token1, $token2);
    }

    public function test_validate_devuelve_objeto_con_token_valido(): void
    {
        $usuario = new Usuario();
        $usuario->id = 1;
        $usuario->email = 'test@example.com';
        $usuario->rol_id = 2;

        $token = $this->provider->generateAccessToken($usuario);
        $payload = $this->provider->validate($token);

        $this->assertIsObject($payload);
        $this->assertEquals(1, $payload->sub);
        $this->assertEquals('test@example.com', $payload->email);
    }

    public function test_validate_devuelve_null_con_token_invalido_o_adulterado(): void
    {
        $payload = $this->provider->validate('token.falso.invalido');
        
        $this->assertNull($payload);
    }
}