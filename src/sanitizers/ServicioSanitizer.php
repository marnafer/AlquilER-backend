<?php

namespace App\Sanitizers;

class ServicioSanitizer
{
    /**
     * Sanitiza un ID de servicio
     */
    public static function sanitizarIdServicio($id): ?int
    {
        if (
            !is_string($id)
            && !is_int($id)
        ) {
            return null;
        }

        if (
            is_string($id)
            && !ctype_digit($id)
        ) {
            return null;
        }

        $id = (int) $id;

        return $id > 0 ? $id : null;
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

    /**
     * Sanitiza payload de actualización de servicio
     */
    public static function sanitizarActualizacionServicio(array $data): array 
    {
        return [
            'nombre' => isset($data['nombre'])
                ? self::sanitizarNombre($data['nombre'])
                : null,
        ];
    }
}