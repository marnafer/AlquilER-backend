<?php

namespace App\Sanitizers;

class ServicioSanitizer
{
    /**
     * Sanitiza un ID de servicio
     */
    public static function sanitizarIdServicio($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        if (!is_numeric($id)) {
            return null;
        }

        return (int) $id;
    }

    /**
     * Sanitiza nombre de servicio
     */
    public static function sanitizarNombre($nombre): ?string
    {
        if ($nombre === null || $nombre === '') {
            return null;
        }

        $nombre = trim($nombre);
        $nombre = preg_replace('/\s+/u', ' ', $nombre);

        return $nombre;
    }

    /**
     * Sanitiza payload completo de servicio
     */
    public static function sanitizarServicio(array $data): array
    {
        return [
            'id' => self::sanitizarIdServicio($data['id'] ?? null),
            'nombre' => self::sanitizarNombre($data['nombre'] ?? null),
        ];
    }
}