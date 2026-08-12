<?php

namespace App\Middlewares;

use App\Helpers\JwtHelper;
use App\Helpers\Response;

class AutenticadorMiddleware
{
    public static function verificar()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        // 1. Verificar que exista
        if (!$authHeader) {
            Response::unauthorized('Token requerido');
        }

        // 2. Verificar formato Bearer
        $authHeader = trim($authHeader);

        if (!str_starts_with($authHeader, 'Bearer ')) {
            Response::unauthorized('Formato de token inválido');
        }

        // 3. Extraer token
        $token = trim(substr($authHeader, 7));

        if ($token === '') {
            Response::unauthorized('Token requerido');
        }

        // 4. Validar token
        $user = JwtHelper::verificarToken($token);

        if (!$user) {
            Response::unauthorized('Token inválido o expirado');
        }

        return $user;
    }

    /**
     * Verifica que el usuario autenticado sea administrador.
     */
    public static function soloAdmin()
    {
        $user = self::verificar();

        if ((int) $user->rol_id !== 2) {
            Response::forbidden('Solo administradores');
        }

        return $user;
    }
}