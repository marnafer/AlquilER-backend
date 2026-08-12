<?php

namespace App\Validators;

class ServicioValidator
{
    /**
     * Valida ID de servicio
     */
    public static function validarIdServicio($id): array
    {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' => 'El ID de servicio es requerido. Debe ser un numero entero positivo'
            ];
        }

        if (!is_numeric($id)) {
            return [
                'success' => false,
                'error' => 'El ID de servicio debe ser numérico'
            ];
        }

        if ((int)$id <= 0) {
            return [
                'success' => false,
                'error' => 'El ID de servicio debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida nombre de servicio
     */
    public static function validarNombreServicio(?string $nombre): array
    {
        if ($nombre === null || $nombre === '') {
            return [
                'success' => false,
                'error' => 'El nombre del servicio es requerido'
            ];
        }

        $len = mb_strlen($nombre);

        if ($len < 3) {
            return [
                'success' => false,
                'error' => 'El nombre debe tener al menos 3 caracteres'
            ];
        }

        if ($len > 50) {
            return [
                'success' => false,
                'error' => 'El nombre no puede superar los 50 caracteres'
            ];
        }

        if (!preg_match('/^[\p{L}\p{N}\s\-\&]+$/u', $nombre)) {
            return [
                'success' => false,
                'error' => 'El nombre solo puede contener letras, números, espacios, guiones y &'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida payload completo de servicio
     */
    public static function validarServicio(array $data, bool $requerirId = false): array
    {
        $errores = [];

        if ($requerirId) {
            $id = self::validarIdServicio($data['id'] ?? null);

            if (!$id['success']) {
                $errores['id'] = $id['error'];
            }
        }

        $nombre = self::validarNombreServicio($data['nombre'] ?? null);

        if (!$nombre['success']) {
            $errores['nombre'] = $nombre['error'];
        }

        if (!empty($errores)) {
            return [
                'success' => false,
                'errors' => $errores
            ];
        }

        return [
            'success' => true,
            'errors' => null
        ];
    }
}