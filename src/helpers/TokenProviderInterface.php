<?php
namespace App\Helpers;

use App\Models\Usuario;

interface TokenProviderInterface
{
    /**
     * Genera un token de acceso (retrocompatible)
     */
    public function generate(Usuario $usuario): string;

    /**
     * Valida un token
     */
    public function validate(string $token): ?object;

    /**
     * Genera un token de acceso
     */
    public function generateAccessToken(Usuario $usuario): string;

    /**
     * Genera un token de refresco
     */
    public function generateRefreshToken(Usuario $usuario): string;

    /**
     * Genera ambos tokens (acceso y refresco)
     */
    public function generateTokens(Usuario $usuario): array;

    /**
     * Valida un token de refresco
     */
    public function validateRefreshToken(string $token): ?object;
}