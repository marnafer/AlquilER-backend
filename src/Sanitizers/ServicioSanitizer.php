<?php

namespace App\Sanitizers;

class ServicioSanitizer
{
    public static function sanitizar(
        array $data
    ): array {
        return [
            'id' => self::sanitizarId(
                $data['id'] ?? null
            ),
            'nombre' => self::sanitizarNombre(
                $data['nombre'] ?? null
            ),
        ];
    }

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

    public static function sanitizarNombre(
        $nombre
    ): ?string {
        if ($nombre === null || $nombre === '') {
            return null;
        }

        $nombre = trim($nombre);
        $nombre = preg_replace(
            '/\s+/u',
            ' ',
            $nombre
        );
        $nombre = strip_tags($nombre);
        $nombre = htmlspecialchars(
            $nombre,
            ENT_QUOTES,
            'UTF-8'
        );

        return $nombre;
    }

    public static function sanitizarActualizacion(
        array $data
    ): array {
        return [
            'nombre' => array_key_exists(
                'nombre',
                $data
            )
                ? self::sanitizarNombre(
                    $data['nombre']
                )
                : null,
        ];
    }
}