<?php

namespace App\Sanitizers;

class LocalidadSanitizer
{
    public static function sanitizarLocalidad(array $data): array
    {
        return [
            'id' => self::sanitizarIdLocalidad($data['id'] ?? null),
            'nombre' => self::sanitizarNombreLocalidad($data['nombre'] ?? null),
            'codigo_postal' => self::sanitizarCodigoPostal($data['codigo_postal'] ?? null),
            'provincia_id' => self::sanitizarProvinciaId($data['provincia_id'] ?? null),
        ];
    }

    public static function sanitizarActualizacionLocalidad(array $data): array
    {
        $resultado = [];

        if (array_key_exists('nombre', $data)) {
            $resultado['nombre'] = self::sanitizarNombreLocalidad($data['nombre']);
        }

        if (array_key_exists('codigo_postal', $data)) {
            $resultado['codigo_postal'] = self::sanitizarCodigoPostal($data['codigo_postal']);
        }

        if (array_key_exists('provincia_id', $data)) {
            $resultado['provincia_id'] = self::sanitizarProvinciaId($data['provincia_id']);
        }

        return $resultado;
    }

    public static function sanitizarIdLocalidad($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $idSanitizado = filter_var($id, FILTER_VALIDATE_INT);

        return ($idSanitizado !== false && $idSanitizado > 0)
            ? $idSanitizado
            : null;
    }

    public static function sanitizarNombreLocalidad($nombre): ?string
    {
        if (!is_string($nombre)) {
            return null;
        }

        $nombre = trim($nombre);
        $nombre = preg_replace('/\s+/u', ' ', $nombre);
        $nombre = mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8');

        return mb_substr($nombre, 0, 150);
    }

    public static function sanitizarCodigoPostal($codigoPostal): ?string
    {
        if (!is_string($codigoPostal) && $codigoPostal !== null) {
            $codigoPostal = (string) $codigoPostal;
        }

        if ($codigoPostal === null || trim((string) $codigoPostal) === '') {
            return null;
        }

        $codigoPostal = trim((string) $codigoPostal);
        $codigoPostal = preg_replace('/\s+/u', ' ', $codigoPostal);

        return mb_substr($codigoPostal, 0, 20);
    }

    public static function sanitizarProvinciaId($provinciaId): ?int
    {
        if ($provinciaId === null || $provinciaId === '') {
            return null;
        }

        $provinciaIdSanitizado = filter_var($provinciaId, FILTER_VALIDATE_INT);

        return ($provinciaIdSanitizado !== false && $provinciaIdSanitizado > 0)
            ? $provinciaIdSanitizado
            : null;
    }
}