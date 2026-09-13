<?php

namespace App\Helpers;

use App\Helpers\JwtHelper;
use App\Models\Usuario;

class JwtProvider implements TokenProviderInterface
{
    /**
     * Genera un token de acceso (retrocompatible)
     */
    public function generate(Usuario $usuario): string
    {
        return JwtHelper::generarAccessToken($usuario);
    }

    /**
     * Valida un token (verifica cualquier tipo de token)
     */
    public function validate(string $token): ?object
    {
        return JwtHelper::verificarToken($token);
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