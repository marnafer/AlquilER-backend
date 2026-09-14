<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Usuario;

class JwtProvider implements TokenProviderInterface
{
    /**
     * Genera un token de acceso
     */
    public function generateAccessToken(Usuario $usuario): string
    {
        $payload = [
            'iss' => 'alquiler-backend',
            'iat' => time(),
            'exp' => time() + 900,
            'sub' => $usuario->id,
            'email' => $usuario->email,
            'rol_id' => $usuario->rol_id,
        ];

        return JWT::encode($payload, JWT_KEY, JWT_ALGORITHM);
    }

    /**
     * Genera un token de refresco
     */
    public function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(40));
    }

    /**
     * Valida un token
     */
    public function validate(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key(JWT_KEY, JWT_ALGORITHM));
        } catch (\Exception $e) {
            return null;
        }
    }
}