<?php

namespace App\Middlewares;

use App\Helpers\JwtHelper;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ForbiddenException;

class AutenticadorMiddleware
{
    public static function verificar()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        // 1. Verificar que exista
        if (!$authHeader) {
            throw new UnauthorizedException('Token requerido');
        }

        // 2. Verificar formato Bearer
        $authHeader = trim($authHeader);

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new UnauthorizedException('Formato de token inválido');
        }

        // 3. Extraer token
        $token = trim(substr($authHeader, 7));

        if ($token === '') {
            throw new UnauthorizedException('Token requerido');
        }

        // 4. Validar token
        $user = JwtHelper::verificarToken($token);

        if (!$user) {
            throw new UnauthorizedException('Token inválido o expirado');
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
            throw new ForbiddenException('Solo administradores');
        }

        return $user;
    }
}