<?php
namespace App\Helpers;

use App\Models\Usuario;

interface TokenProviderInterface
{
    public function generateAccessToken(Usuario $usuario): string;
    
    public function generateRefreshToken(): string;

    public function validate(string $token): ?object;
}