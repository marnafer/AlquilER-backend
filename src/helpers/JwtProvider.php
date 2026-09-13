<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Usuario;

class JwtProvider implements TokenProviderInterface
{
<<<<<<< HEAD
    /**
     * Genera un token de acceso (retrocompatible)
     */
    public function generate(Usuario $usuario): string
    {
        return JwtHelper::generarAccessToken($usuario);
=======
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
>>>>>>> c9460ea80694538dda38eefb86136b58a78448c8
    }

    /**
     * Valida un token (verifica cualquier tipo de token)
     */
    public function validate(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key(JWT_KEY, JWT_ALGORITHM));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Genera un token de acceso
     */
    public function generateAccessToken(Usuario $usuario): string
    {
        return JwtHelper::generarAccessToken($usuario);
    }

    /**
     * Genera un token de refresco
     */
    public function generateRefreshToken(Usuario $usuario): string
    {
        return JwtHelper::generarRefreshToken($usuario);
    }

    /**
     * Genera ambos tokens
     */
    public function generateTokens(Usuario $usuario): array
    {
        return JwtHelper::generarTokens($usuario);
    }

    /**
     * Valida un token de refresco
     */
    public function validateRefreshToken(string $token): ?object
    {
        return JwtHelper::verificarTokenPorTipo($token, 'refresh');
    }
}