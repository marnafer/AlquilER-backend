<?php

namespace App\Sanitizers;

class PropiedadServicioSanitizer
{
    public static function sanitizar(array $data): array
    {
        return [
            'id' => self::sanitizarId(
                $data['id'] ?? null
            ),
            'propiedad_id' => self::sanitizarId(
                $data['propiedad_id'] ?? null
            ),
            'servicio_id' => self::sanitizarId(
                $data['servicio_id'] ?? null
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

    public static function sanitizarServicioIds(
        array $ids
    ): array {
        return array_map(
            fn ($id) => self::sanitizarId($id),
            $ids
        );
    }
}