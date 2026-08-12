<?php

namespace App\Sanitizers;

class PropiedadServicioSanitizer
{
    /**
     * Sanitiza payload completo
     */
    public static function sanitizar(array $data): array
    {
        return [
            'id' => self::sanitizarId($data['id'] ?? null),
            'propiedad_id' => self::sanitizarPropiedadId($data['propiedad_id'] ?? null),
            'servicio_id' => self::sanitizarServicioId($data['servicio_id'] ?? null),
        ];
    }

    /**
     * Sanitiza ID de la relación
     */
    public static function sanitizarId($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var($id, FILTER_VALIDATE_INT);

        return ($id !== false && $id > 0) ? $id : null;
    }

    /**
     * Sanitiza ID de propiedad
     */
    public static function sanitizarPropiedadId($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var($id, FILTER_VALIDATE_INT);

        return ($id !== false && $id > 0) ? $id : null;
    }

    /**
     * Sanitiza ID de servicio
     */
    public static function sanitizarServicioId($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var($id, FILTER_VALIDATE_INT);

        return ($id !== false && $id > 0) ? $id : null;
    }

    /**
     * Sanitiza solo IDs (casos rápidos tipo sync o validación mínima)
     */
    public static function sanitizarIds(array $data): array
    {
        return [
            'propiedad_id' => self::sanitizarPropiedadId($data['propiedad_id'] ?? null),
            'servicio_id' => self::sanitizarServicioId($data['servicio_id'] ?? null),
        ];
    }
}