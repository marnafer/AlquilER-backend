<?php
namespace App\Helpers;

use App\Models\Usuario;

interface TokenProviderInterface
{
<<<<<<< HEAD
    /**
     * Genera un token de acceso (retrocompatible)
     */
    public function generate(Usuario $usuario): string;
=======
    public function generateAccessToken(Usuario $usuario): string;
    
    public function generateRefreshToken(): string;
>>>>>>> c9460ea80694538dda38eefb86136b58a78448c8

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