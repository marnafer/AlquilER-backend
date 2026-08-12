<?php

namespace App\Validators;

class RolValidator
{
    /**
     * Validar todos los datos de un rol
     */
    public static function validarRol($data, $requerirId = false): array
    {
        $errores = [];

        // ID
        if ($requerirId) {

            $resultado = self::validarIdRequerido(
                $data['id'] ?? null,
                'rol'
            );

            if (!$resultado['success']) {
                $errores['id'] = $resultado['error'];
            }
        }

        // Nombre
        $resultado = self::validarNombreRol(
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
            'errors' => null,
            'data' => [
                'id' => $data['id'] ?? null,
                'nombre' => $data['nombre']
            ]
        ];
    }

    /**
     * Validar ID requerido
     */
    public static function validarIdRequerido(
        $id,
        $campo = ''
    ): array {

        if ($id === null || $id === '') {

            return [
                'success' => false,
                'error' => "El ID de $campo es requerido"
            ];
        }

        if (!is_numeric($id) || $id <= 0) {

            return [
                'success' => false,
                'error' => "El ID de $campo debe ser positivo"
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Validar nombre del rol
     */
    public static function validarNombreRol($nombre): array
    {
        if ($nombre === null || $nombre === '') {
            return [
                'success' => false,
                'error' => 'El nombre del rol es requerido'
            ];
        }

        $nombre = trim($nombre);

        if (strlen($nombre) < 3) {
            return [
                'success' => false,
                'error' => 'El nombre debe tener al menos 3 caracteres'
            ];
        }

        if (strlen($nombre) > 30) {
            return [
                'success' => false,
                'error' => 'El nombre no puede exceder los 30 caracteres'
            ];
        }

        if (!preg_match('/^[a-zA-ZáéíóúñÁÉÍÓÚ\s]+$/u', $nombre)) {
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
     * Validar creación
     */
    public static function validarCrearRol(
        $data
    ): array {

        return self::validarRol(
            $data,
            false
        );
    }

    /**
     * Validar actualización
     */
    public static function validarActualizarRol(
        $data
    ): array {

        return self::validarRol(
            $data,
            true
        );
    }

    /**
     * Validar solo ID
     */
    public static function validarSoloIdRol(
        $id
    ): array {

        $resultado = self::validarIdRequerido(
            $id,
            'rol'
        );

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