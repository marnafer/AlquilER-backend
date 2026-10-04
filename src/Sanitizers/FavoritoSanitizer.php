<?php

declare(strict_types=1);

namespace App\Sanitizers;

class FavoritoSanitizer
{
    /**
     * Sanitizar datos para crear un favorito.
     *
     * El usuario_id no se recibe del cliente.
     * Se obtiene desde el JWT en el Service/Controller.
     */
    public static function sanitizarCrear(array $data): array
    {
        return [
            'propiedad_id' => self::sanitizarId(
                $data['propiedad_id'] ?? null
            )
        ];
    }

    /**
     * Sanitizar un ID recibido desde una ruta.
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
     * Sanitizar el ID de una propiedad.
     */
    public static function sanitizarPropiedadId($propiedadId): ?int
    {
        return self::sanitizarId($propiedadId);
    }
}