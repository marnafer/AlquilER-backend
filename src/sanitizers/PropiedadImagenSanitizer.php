<?php

namespace App\Sanitizers;

class PropiedadImagenSanitizer
{
    public static function sanitizarIdPropiedadImagen($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        if (!is_numeric($id)) {
            return null;
        }

        $id = (int) $id;

        return $id > 0 ? $id : null;
    }

    public static function sanitizarPropiedadImagen(array $data): array
    {
        return [
            'propiedad_id' => isset($data['propiedad_id'])
                ? filter_var($data['propiedad_id'], FILTER_VALIDATE_INT)
                : null,

            'descripcion' => isset($data['descripcion']) && trim($data['descripcion']) !== ''
                ? htmlspecialchars(trim($data['descripcion']), ENT_QUOTES, 'UTF-8')
                : null,
        ];
    }
}