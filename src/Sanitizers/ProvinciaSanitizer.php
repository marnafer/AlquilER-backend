<?php

namespace App\Sanitizers;

class ProvinciaSanitizer
{
    /**
     * Sanitizar datos para crear provincia.
     */
    public static function sanitizarCrear(array $data): array
    {
        return [
            'nombre' => self::sanitizarNombre(
                $data['nombre'] ?? null
            )
        ];
    }

    /**
     * Sanitizar ID.
     */
    public static function sanitizarId($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var(
            $id,
            FILTER_VALIDATE_INT
        );

        return (
            $id !== false &&
            $id > 0
        )
            ? $id
            : null;
    }

    /**
     * Sanitizar nombre.
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

        return mb_substr($nombre, 0, 100);
    }

    /**
     * Sanitizar datos para actualizar provincia.
     */
    public static function sanitizarActualizar(array $data): array
    {
        $sanitizado = [];

        if (array_key_exists('nombre', $data)) {
            $sanitizado['nombre'] = self::sanitizarNombre(
                $data['nombre']
            );
        }

        return $sanitizado;
    }
}