<?php

namespace App\Validators;

class CategoriaValidator
{
    /**
     * Valida ID (entero positivo)
     * Retorna ['success'=>bool, 'error'=>string|null]
     */
    public static function validarIdCategoria($id)
    {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' => 'El ID de categoría es requerido'
            ];
        }

        if (!is_numeric($id)) {
            return [
                'success' => false,
                'error' => 'El ID de categoría debe ser numérico'
            ];
        }

        if ((int)$id <= 0) {
            return [
                'success' => false,
                'error' => 'El ID de categoría debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida nombre de categoría
     */
    public static function validarNombre(?string $nombre): array
    {
        if ($nombre === null || $nombre === '') {
            return ['success' => false, 'error' => 'El nombre de la categoría es requerido'];
        }

        $len = mb_strlen($nombre);
        if ($len < 3) {
            return ['success' => false, 'error' => 'El nombre debe tener al menos 3 caracteres'];
        }
        if ($len > 50) {
            return ['success' => false, 'error' => 'El nombre no puede exceder los 50 caracteres'];
        }

        // Permitir letras Unicode, números, espacios, guiones y &
        if (!preg_match('/^[\p{L}\p{N}\s\-\&]+$/u', $nombre)) {
            return ['success' => false, 'error' => 'El nombre solo puede contener letras, números, espacios, guiones y &'];
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * Valida payload completo. Espera datos ya sanitizados.
     * Retorna ['success'=>bool, 'errors'=>array|null, 'data'=>array|null]
     */
    public static function validarCategoria(array $data, bool $requerirId = false)
    {
        $errores = [];

        if ($requerirId) {

            $resultado = self::validarIdCategoria(
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

    // Valida solo el ID (por ejemplo para rutas con ID). 
    //Retorna ['success'=>bool, 'message'=>string, 'errors'=>array|null]
    public static function validarSoloIdCategoria($id)
    {
        $resultado = self::validarIdCategoria($id);

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