<?php

namespace App\Sanitizers;

class PropiedadImagenSanitizer
{
    
    /**
     * Sanitiza un ID
     */
    public static function sanitizarId($id): ?int
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
     * Sanitiza un ID de imagen.
     */
    public static function sanitizarIdPropiedadImagen($id): ?int
    {
        return self::sanitizarId($id);
    }

    /**
     * Sanitiza el ID de una propiedad.
     */
    public static function sanitizarIdPropiedad($id): ?int
    {
        return self::sanitizarId($id);
    }

    /**
     * Sanitiza la descripción de una imagen.
     */
    public static function sanitizarDescripcion($descripcion): ?string
    {
        if (!is_string($descripcion)) {
            return null;
        }

        $descripcion = trim($descripcion);

        if ($descripcion === '') {
            return null;
        }

        return mb_substr($descripcion, 0, 300);
    }

    /**
     * Sanitiza todo el payload de una imagen.
     */
    public static function sanitizarPropiedadImagen(array $data): array
    {
        return [
            'propiedad_id' => self::sanitizarIdPropiedad(
                $data['propiedad_id'] ?? null
            ),

            'descripcion' => self::sanitizarDescripcion(
                $data['descripcion'] ?? null
            ),
        ];
    }
}