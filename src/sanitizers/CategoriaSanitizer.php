<?php

namespace App\Sanitizers;

class CategoriaSanitizer
{
    /**
     * Sanitiza un ID (por ejemplo de la URL)
     */
    public static function sanitizarIdCategoria($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        $val = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
        return $val === false ? null : (int)$val;
    }

    /**
    * Sanitiza un nombre de categoría
     */
    public static function sanitizarNombre($nombre)
    {
        if (!$nombre) {
            return null;
        }

        $nombre = trim($nombre);
        $nombre = preg_replace('/\s+/u', ' ', $nombre);
        $nombre = mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8');

        return substr(
            htmlspecialchars(
                $nombre,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ),
            0,
            50
        );
    }

    /**
     * Sanitiza todo el payload de categoría
     */
    public static function sanitizarCategoria(array $data): array
    {
        return [
            'id' => self::sanitizarIdCategoria($data['id'] ?? null),
            'nombre' => isset($data['nombre']) && $data['nombre'] !== '' ? self::sanitizarNombre((string)$data['nombre']) : null,
        ];
    }
}