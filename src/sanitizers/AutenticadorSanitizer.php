<?php

declare(strict_types=1);

namespace App\Sanitizers;

class AutenticadorSanitizer
{
    public static function sanitizarLogin(array $data): array
    {
        return [
            'email' => is_string($data['email'] ?? null)
                ? trim($data['email'])
                : ($data['email'] ?? null),

            'contrasena' => $data['contrasena'] ?? null,
        ];
    }

    public static function sanitizarRefresh(array $data): array
    {
        return [
            'refresh_token' => is_string($data['refresh_token'] ?? null)
                ? trim($data['refresh_token'])
                : ($data['refresh_token'] ?? null),
        ];
    }

    public static function sanitizarLogout(array $data): array
    {
        return [
            'refresh_token' => isset($data['refresh_token'])
                ? trim((string) $data['refresh_token'])
                : null,
        ];
    }
}