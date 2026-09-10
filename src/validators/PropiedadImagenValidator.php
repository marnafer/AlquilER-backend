<?php

namespace App\Validators;

class PropiedadImagenValidator
{
    public static function validarSoloIdPropiedadImagen($id): array
    {
        if ($id === null) {
            return [
                'success' => false,
                'message' => 'Error de validación',
                'errors' => [
                    'id' => 'Ingrese un ID de imagen valido'
                ]
            ];
        }

        if (!is_numeric($id) || (int) $id <= 0) {
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

    public static function validarCrearPropiedadImagen(array $data): array
    {
        $errores = [];

        if ($data['propiedad_id'] === null) {
            $errores['propiedad_id'] = 'El ID de propiedad es requerido';
        } elseif (
            !is_numeric($data['propiedad_id'])
            || (int) $data['propiedad_id'] <= 0
        ) {
            $errores['propiedad_id'] = 'El ID de propiedad debe ser un entero positivo';
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