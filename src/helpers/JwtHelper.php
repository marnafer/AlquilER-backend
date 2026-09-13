<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper {

    /**
     * Genera un token de acceso (corta duración - por defecto 15 minutos)
     */
    public static function generarAccessToken($usuario, $expirationSeconds = null) {
        // Si no se especifica expiración, usar 15 minutos
        $expirationSeconds = $expirationSeconds ?? (15 * 60);

        $payload = [
            'iss' => 'sistema-alquiler',
            'iat' => time(),
            'exp' => time() + $expirationSeconds,
            'sub' => $usuario->id,
            'email' => $usuario->email,
            'rol_id' => $usuario->rol_id,
            'type' => 'access'
        ];

        return JWT::encode($payload, JWT_KEY, JWT_ALGORITHM);
    }

    /**
     * Genera un token de refresco (larga duración - por defecto 7 días)
     */
    public static function generarRefreshToken($usuario, $expirationSeconds = null) {
        // Si no se especifica expiración, usar 7 días
        $expirationSeconds = $expirationSeconds ?? (7 * 24 * 60 * 60);

        $payload = [
            'iss' => 'sistema-alquiler',
            'iat' => time(),
            'exp' => time() + $expirationSeconds,
            'sub' => $usuario->id,
            'email' => $usuario->email,
            'type' => 'refresh'
        ];

        return JWT::encode($payload, JWT_KEY, JWT_ALGORITHM);
    }

    /**
     * Genera ambos tokens (acceso y refresco)
     */
    public static function generarTokens($usuario) {
        return [
            'access_token' => self::generarAccessToken($usuario),
            'refresh_token' => self::generarRefreshToken($usuario)
        ];
    }

    /**
     * Verifica y decodifica un token
     */
    public static function verificarToken($token) {
        try {
            return JWT::decode($token, new Key(JWT_KEY, JWT_ALGORITHM));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verifica que un token sea de un tipo específico
     */
    public static function verificarTokenPorTipo($token, $tipo) {
        $payload = self::verificarToken($token);
        
        if (!$payload || !isset($payload->type) || $payload->type !== $tipo) {
            return null;
        }
        
        return $payload;
    }

    /**
     * Método retrocompatible: genera solo el token de acceso
     */
    public static function generarToken($usuario) {
        return self::generarAccessToken($usuario);
    }
}