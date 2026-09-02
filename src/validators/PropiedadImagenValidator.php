<?php

namespace App\Validators;

class PropiedadImagenValidator
{
    private const MAX_SIZE = 5 * 1024 * 1024;
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

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

    public static function validarCrearPropiedadImagen(array $data, $file): array
    {
        $errores = [];

        if ($data['propiedad_id'] === null) {
            $errores['propiedad_id'] = 'El ID de propiedad es requerido';
        } elseif (!is_numeric($data['propiedad_id']) || (int) $data['propiedad_id'] <= 0) {
            $errores['propiedad_id'] = 'El ID de propiedad debe ser un entero positivo';
        }

        if (!$file || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errores['imagen'] = 'Debe enviar una imagen';
        } else {
            if ($file['size'] > self::MAX_SIZE) {
                $errores['imagen'] = 'La imagen supera 5MB';
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, self::ALLOWED_MIMES, true)) {
                $errores['imagen'] = 'Formato no permitido';
            }
        }

        if (!empty($errores)) {
            return [
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $errores
            ];
        }

        return [
            'success' => true,
            'message' => 'OK',
            'errors' => null
        ];
    }

}