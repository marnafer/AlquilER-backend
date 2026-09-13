<?php
namespace App\Helpers;

use App\Models\Usuario;

interface TokenProviderInterface
{
    /**
     * Genera un token de acceso
     */
    public function generateAccessToken(Usuario $usuario): string;

    /**
     * Genera un token de refresco
     */
    public function generateRefreshToken(): string;

    /**
     * Valida un token
     */
    public function validate(string $token): ?object;
}