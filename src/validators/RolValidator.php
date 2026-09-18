<?php

namespace App\Validators;

class RolValidator
{
    /**
     * Valida ID (entero positivo)
     */
    public static function validarIdRol($id)
    {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' => 'El ID de rol es requerido'
            ];
        }

        if (!is_numeric($id)) {
            return [
                'success' => false,
                'error' => 'El ID de rol debe ser numérico'
            ];
        }

        if ((int) $id <= 0) {
            return [
                'success' => false,
                'error' => 'El ID de rol debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida nombre de rol
     */
    public static function validarNombre(?string $nombre): array
    {
        if ($nombre === null || $nombre === '') {
            return [
                'success' => false,
                'error' => 'El nombre del rol es requerido'
            ];
        }

        $len = mb_strlen($nombre);

        if ($len < 3) {
            return [
                'success' => false,
                'error' => 'El nombre debe tener al menos 3 caracteres'
            ];
        }

        if ($len > 30) {
            return [
                'success' => false,
                'error' => 'El nombre no puede exceder los 30 caracteres'
            ];
        }

        if (!preg_match('/^[\p{L}\s]+$/u', $nombre)) {
            return [
                'success' => false,
                'error' => 'El nombre solo puede contener letras y espacios'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida payload completo.
     * Espera datos ya sanitizados.
     */
    public static function validarRol(
        array $data,
        bool $requerirId = false
    ): array {
        $errores = [];

        if ($requerirId) {
            $resultado = self::validarIdRol(
                $data['id'] ?? null
            );

            if (!$resultado['success']) {
                $errores['id'] = $resultado['error'];
            }
        }

        $resultado = self::validarNombre(
            $data['nombre'] ?? null
        );

        if (!$resultado['success']) {
            $errores['nombre'] = $resultado['error'];
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
            'message' => 'Validación exitosa',
            'errors' => null
        ];
    }

    /**
     * Valida solo el ID.
     */
    public static function validarSoloIdRol($id): array
    {
        $resultado = self::validarIdRol($id);

        if (!$resultado['success']) {
            return [
                'success' => false,
                'message' => 'ID inválido',
                'errors' => [
                    'id' => $resultado['error']
                ]
            ];
        }

        return [
            'success' => true,
            'message' => 'ID válido',
            'errors' => null
        ];
    }
}