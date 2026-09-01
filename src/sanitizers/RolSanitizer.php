<?php

namespace App\Sanitizers;

class RolSanitizer
{
    /**
     * Sanitizar rol completo
     */
    public static function sanitizarRol($data): array
    {
        return [
            'id' => self::sanitizarIdRol($data['id'] ?? null),
            'nombre' => self::sanitizarNombreRol($data['nombre'] ?? null)
        ];
    }

    /**
     * Sanitizar ID
     */
    public static function sanitizarIdRol($id)
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var(
            $id,
            FILTER_VALIDATE_INT
        );

        return ($id !== false && $id > 0)
            ? $id
            : null;
    }

    /**
     * Sanitizar nombre
     */
    public static function sanitizarNombreRol($nombre): ?string
{
    if ($nombre === null || $nombre === '') {
        return null;
    }

    $nombre = trim((string) $nombre);
    $nombre = preg_replace('/\s+/', ' ', $nombre);

    return $nombre !== '' ? $nombre : null;
}

    /**
     * Sanitizar solo nombre
     */
    public static function sanitizarSoloNombreRol($nombre)
    {
        return self::sanitizarNombreRol($nombre);
    }

    public static function sanitizarActualizacionRol(array $data): array
    {
        return [
            'nombre' => isset($data['nombre'])
                ? self::sanitizarNombreRol((string) $data['nombre'])
                : null,
        ];
    }
}