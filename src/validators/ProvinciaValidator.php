<?php

namespace App\Validators;

class ProvinciaValidator
{
    public static function validarProvincia(array $data, bool $requerirId = false): array
    {
        $errores = [];

        if ($requerirId) {
            $resultado = self::validarIdProvincia($data['id'] ?? null);

            if (!$resultado['success']) {
                $errores['id'] = $resultado['error'];
            }
        }

        $resultado = self::validarNombreProvincia($data['nombre'] ?? null);

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

    public static function validarIdProvincia($id): array
    {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' => 'El ID de provincia es requerido. Debe ser un numero entero positivo.'
            ];
        }

        if (!is_numeric($id)) {
            return [
                'success' => false,
                'error' => 'El ID de provincia debe ser numérico'
            ];
        }

        if ((int)$id <= 0) {
            return [
                'success' => false,
                'error' => 'El ID de provincia debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    public static function validarNombreProvincia($nombre): array
    {
        if ($nombre === null || $nombre === '') {
            return [
                'success' => false,
                'error' => 'El nombre es requerido'
            ];
        }

        $nombre = trim($nombre);

        if (mb_strlen($nombre) < 3) {
            return [
                'success' => false,
                'error' => 'El nombre debe tener al menos 3 caracteres'
            ];
        }

        if (mb_strlen($nombre) > 100) {
            return [
                'success' => false,
                'error' => 'El nombre no puede exceder los 100 caracteres'
            ];
        }

        if (is_numeric($nombre)) {
            return [
                'success' => false,
                'error' => 'El nombre no puede ser solo números'
            ];
        }

        if (!preg_match('/^[a-zA-ZáéíóúñÑÁÉÍÓÚ\s]+$/u', $nombre)) {
            return [
                'success' => false,
                'error' => 'Solo letras y espacios'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    public static function validarCrearProvincia(array $data): array
    {
        return self::validarProvincia($data, false);
    }

    public static function validarActualizarProvincia(array $data): array
    {
        return self::validarProvincia($data, true);
    }

    public static function validarSoloIdProvincia($id): array
    {
        $resultado = self::validarIdProvincia($id);

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