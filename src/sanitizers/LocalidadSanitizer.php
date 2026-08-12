<?php

namespace App\Sanitizers;

class LocalidadSanitizer
{
    public static function sanitizarLocalidad($data): array
    {
        return [
            'id' => self::sanitizarIdLocalidad($data['id'] ?? null),
            'nombre' => self::sanitizarNombreLocalidad($data['nombre'] ?? null),
            'codigo_postal' => self::sanitizarCodigoPostal($data['codigo_postal'] ?? null),
            'provincia_id' => self::sanitizarProvinciaId($data['provincia_id'] ?? null)
        ];
    }

    public static function sanitizarIdLocalidad($id)
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var($id, FILTER_VALIDATE_INT);

        return ($id !== false && $id > 0)
            ? $id
            : null;
    }

    public static function sanitizarNombreLocalidad($nombre)
    {
        if (!$nombre) {
            return null;
        }

        $nombre = trim($nombre);
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        $nombre = ucwords(strtolower($nombre));
        $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');

        return substr($nombre, 0, 150);
    }

    public static function sanitizarCodigoPostal($cp)
    {
        if (!$cp) {
            return null;
        }

        $cp = trim($cp);
        $cp = htmlspecialchars($cp, ENT_QUOTES, 'UTF-8');

        return substr($cp, 0, 20);
    }

    public static function sanitizarProvinciaId($provinciaId)
    {
        if ($provinciaId === null || $provinciaId === '') {
            return null;
        }

        $provinciaId = filter_var(
            $provinciaId,
            FILTER_VALIDATE_INT
        );

        return ($provinciaId !== false && $provinciaId > 0)
            ? $provinciaId
            : null;
    }
}