<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Usuario;

class JwtProvider implements TokenProviderInterface
{
    public function generateAccessToken(Usuario $usuario): string
    {
        $payload = [
            'iss' => 'alquiler-backend',
            'iat' => time(),
            // Idealmente, el access token debería durar poco (ej: 15 minutos = 900 seg)
            'exp' => time() + 900, 
            'sub' => $usuario->id,
            'email' => $usuario->email,
            'rol_id' => $usuario->rol_id
        ];

        return JWT::encode($payload, JWT_KEY, JWT_ALGORITHM);
    }

    public function generateRefreshToken(): string
    {
        // Genera una cadena aleatoria segura de 80 caracteres
        return bin2hex(random_bytes(40));
    }

    public function validate(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key(JWT_KEY, JWT_ALGORITHM));
        } catch (\Exception $e) {
            return null;
        }
    }
}