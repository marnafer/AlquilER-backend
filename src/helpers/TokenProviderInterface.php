<?php
namespace App\Helpers;

use App\Models\Usuario;

interface TokenProviderInterface
{
    public function generate(Usuario $usuario): string;

    public function validate(string $token): ?object;
}