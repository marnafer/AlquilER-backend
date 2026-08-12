<?php

namespace App\Sanitizers;

class ProvinciaSanitizer
{
    public static function sanitizarProvincia(array $data): array
    {
        return [
            'id' => self::sanitizarIdProvincia($data['id'] ?? null),
            'nombre' => self::sanitizarNombreProvincia($data['nombre'] ?? null)
        ];
    }

    public static function sanitizarIdProvincia($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var($id, FILTER_VALIDATE_INT);

        return ($id !== false && $id > 0)
            ? $id
            : null;
    }

    public static function sanitizarNombreProvincia(?string $nombre): ?string
    {
        if (!$nombre) {
            return null;
        }

        $nombre = trim($nombre);
        $nombre = preg_replace('/\s+/u', ' ', $nombre);
        $nombre = mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8');

        return htmlspecialchars(
            $nombre,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}