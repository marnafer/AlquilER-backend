<?php

namespace App\Validators;

class PropiedadImagenValidator
{
    /**
     * Valida un ID de imagen.
     */
    public static function validarSoloIdPropiedadImagen($id): array
    {
        if ($id === null) {
            return [
                'success' => false,
                'message' => 'Error de validación',
                'errors' => [
                    'id' => 'El ID de imagen debe ser un entero positivo'
                ]
            ];
        }

        return [
            'success' => true,
            'message' => 'OK',
            'errors' => null
        ];
    }

    /**
     * Valida los datos necesarios para crear una imagen.
     */
    public static function validarCrearPropiedadImagen(array $data): array
    {
        $errores = [];

        $propiedadId = $data['propiedad_id'] ?? null;

        if ($propiedadId === null) {
            $errores['propiedad_id'] = 'El ID de propiedad es requerido';
        }

        if (!empty($errores)) {
            return [
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $errores,
            ];
        }

        return [
            'success' => true,
            'message' => 'OK',
            'errors' => null,
        ];
    }
}