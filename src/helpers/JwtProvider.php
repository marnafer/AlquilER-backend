<?php

namespace App\Helpers;

use App\Helpers\JwtHelper;
use App\Models\Usuario;

class JwtProvider implements TokenProviderInterface
{
    public function generate(Usuario $usuario): string
    {
        return JwtHelper::generarToken($usuario);
    }

    public function validate(string $token): ?object
    {
        return JwtHelper::verificarToken($token);
    }
}