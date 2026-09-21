<?php

namespace App\Sanitizers;

class RolSanitizer
{
    /**
     * Sanitiza un ID (por ejemplo de la URL)
     */
    public static function sanitizarIdRol($id): ?int
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
     * Sanitiza un nombre de rol
     */
    public static function sanitizarNombre($nombre): ?string
    {
        if (!is_string($nombre)) {
            return null;
        }

        $nombre = trim($nombre);
        $nombre = preg_replace('/\s+/u', ' ', $nombre);
        $nombre = mb_convert_case(
            $nombre,
            MB_CASE_TITLE,
            'UTF-8'
        );

        return mb_substr($nombre, 0, 30);
    }

    /**
     * Sanitiza todo el payload de rol
     */
    public static function sanitizarRol(array $data): array
    {
        return [
            'id' => self::sanitizarIdRol(
                $data['id'] ?? null
            ),
            'nombre' => isset($data['nombre'])
                && $data['nombre'] !== ''
                ? self::sanitizarNombre(
                    (string) $data['nombre']
                )
                : null,
        ];
    }

    /**
     * Sanitiza los datos de actualización
     */
    public static function sanitizarActualizacionRol(
        array $data
    ): array {
        return [
            'nombre' => isset($data['nombre'])
                ? self::sanitizarNombre(
                    (string) $data['nombre']
                )
                : null,
        ];
    }
}