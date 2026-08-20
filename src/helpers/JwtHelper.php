<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper {

    public static function generarToken($usuario) {

        $payload = [
            'iss' => 'sistema-alquiler',
            'iat' => time(),
            // Usamos la constante global de expiración
            'exp' => time() + JWT_EXPIRATION, 
            'sub' => $usuario->id,
            'email' => $usuario->email,
            'rol_id' => $usuario->rol_id
        ];

        // Usamos las constantes globales para la clave y el algoritmo
        return JWT::encode($payload, JWT_KEY, JWT_ALGORITHM);
    }

    public static function verificarToken($token) {

        try {
            // Usamos las constantes globales para decodificar
            return JWT::decode($token, new Key(JWT_KEY, JWT_ALGORITHM));
        } catch (\Exception $e) {
            return null;
        }
    }
}