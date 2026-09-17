<?php

namespace App\Validators;

class ReservaValidator
{
    /**
     * Valida ID de reserva
     */
    public static function validarId($id): array
    {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' => 'El ID de reserva es requerido'
            ];
        }

        if (!is_numeric($id)) {
            return [
                'success' => false,
                'error' => 'El ID de reserva debe ser numérico'
            ];
        }

        if ((int) $id <= 0) {
            return [
                'success' => false,
                'error' => 'El ID de reserva debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida ID de propiedad
     */
    public static function validarPropiedadId($id): array
    {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' => 'El ID de propiedad es requerido'
            ];
        }

        if (!is_numeric($id)) {
            return [
                'success' => false,
                'error' => 'El ID de propiedad debe ser numérico'
            ];
        }

        if ((int) $id <= 0) {
            return [
                'success' => false,
                'error' => 'El ID de propiedad debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida ID de usuario
     */
    public static function validarUsuarioId($id): array
    {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' => 'El ID de usuario es requerido'
            ];
        }

        if (!is_numeric($id)) {
            return [
                'success' => false,
                'error' => 'El ID de usuario debe ser numérico'
            ];
        }

        if ((int) $id <= 0) {
            return [
                'success' => false,
                'error' => 'El ID de usuario debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida estado de reserva
     */
    public static function validarEstado($estado): array
    {
        if ($estado === null || $estado === '') {
            return [
                'success' => false,
                'error' => 'El estado es requerido'
            ];
        }

        if (!is_string($estado)) {
            return [
                'success' => false,
                'error' => 'El estado debe ser texto'
            ];
        }

        if (!in_array(
            $estado,
            ['pendiente', 'confirmada', 'rechazada'],
            true
        )) {
            return [
                'success' => false,
                'error' => 'El estado debe ser pendiente, confirmada o rechazada'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida datos para crear reserva
     *
     * Espera datos ya sanitizados.
     */
    public static function validarCrear(array $data): array
    {
        $errores = [];

        $resultado = self::validarPropiedadId(
            $data['propiedad_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['propiedad_id'] = $resultado['error'];
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
     * Valida datos para actualizar estado
     *
     * Espera datos ya sanitizados.
     */
    public static function validarActualizarEstado(
        array $data
    ): array {
        $errores = [];

        $resultado = self::validarEstado(
            $data['estado'] ?? null
        );

        if (!$resultado['success']) {
            $errores['estado'] = $resultado['error'];
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
     * Valida solamente el ID de reserva
     */
    public static function validarSoloId($id): array
    {
        $resultado = self::validarId($id);

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