<?php

declare(strict_types=1);

namespace App\Sanitizers;

class RecuperarContrasenaSanitizer
{
    public static function sanitizarSolicitud(array $data): array
    {
        return [
            'email' => UsuarioSanitizer::sanitizarSoloEmail(
                $data['email'] ?? null
            ),
        ];
    }

    public static function sanitizarRestablecer(array $data): array
    {
        return [
            'email' => UsuarioSanitizer::sanitizarSoloEmail(
                $data['email'] ?? null
            ),
            'token' => self::sanitizarToken(
                $data['token'] ?? null
            ),
            'contrasena' => trim(
                (string) ($data['contrasena'] ?? '')
            ),
        ];
    }

    public static function sanitizarToken($token): ?string
    {
        $token = trim((string) $token);

        return $token === '' ? null : $token;
    }
}