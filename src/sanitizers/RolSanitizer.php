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
    public static function sanitizarNombreRol($nombre)
    {
        if ($nombre === null || $nombre === '') {
            return null;
        }

        $nombre = trim($nombre);

        $nombre = preg_replace(
            '/\s+/',
            ' ',
            $nombre
        );

        $nombre = strtolower($nombre);

        $nombre = preg_replace(
            '/[^a-záéíóúñ\s]/u',
            '',
            $nombre
        );

        $nombre = htmlspecialchars(
            $nombre,
            ENT_QUOTES,
            'UTF-8'
        );

        if (strlen($nombre) > 30) {
            $nombre = substr($nombre, 0, 30);
        }

        return $nombre;
    }

    /**
     * Sanitizar solo nombre
     */
    public static function sanitizarSoloNombreRol($nombre)
    {
        return self::sanitizarNombreRol($nombre);
    }
}