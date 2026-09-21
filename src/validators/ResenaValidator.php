<?php

namespace App\Validators;

class ResenaValidator
{
    /**
     * Valida ID de reseña
     * Retorna ['success'=>bool, 'error'=>string|null]
     */
    public static function validarId($id)
    {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' => 'El ID de reseña es requerido'
            ];
        }

        if (!is_numeric($id)) {
            return [
                'success' => false,
                'error' => 'El ID de reseña debe ser numérico'
            ];
        }

        if ((int) $id <= 0) {
            return [
                'success' => false,
                'error' => 'El ID de reseña debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida ID de reserva
     * Retorna ['success'=>bool, 'error'=>string|null]
     */
    public static function validarReservaId($id)
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
     * Retorna ['success'=>bool, 'error'=>string|null]
     */
    public static function validarPropiedadId($id)
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
     * Retorna ['success'=>bool, 'error'=>string|null]
     */
    public static function validarUsuarioId($id)
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
     * Valida ID de calificador
     * Retorna ['success'=>bool, 'error'=>string|null]
     */
    public static function validarCalificadorId($id)
    {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' => 'El ID del calificador es requerido'
            ];
        }

        if (!is_numeric($id)) {
            return [
                'success' => false,
                'error' => 'El ID del calificador debe ser numérico'
            ];
        }

        if ((int) $id <= 0) {
            return [
                'success' => false,
                'error' => 'El ID del calificador debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida tipo de reseña
     */
    public static function validarTipo($tipo)
    {
        if ($tipo === null || $tipo === '') {
            return [
                'success' => false,
                'error' => 'El tipo de reseña es requerido'
            ];
        }

        if (!is_string($tipo)) {
            return [
                'success' => false,
                'error' => 'El tipo de reseña debe ser texto'
            ];
        }

        if (!in_array($tipo, ['propiedad', 'inquilino'], true)) {
            return [
                'success' => false,
                'error' => 'El tipo de reseña debe ser propiedad o inquilino'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida calificación
     */
    public static function validarCalificacion($calificacion)
    {
        if ($calificacion === null || $calificacion === '') {
            return [
                'success' => false,
                'error' => 'La calificación es requerida'
            ];
        }

        if (!is_numeric($calificacion)) {
            return [
                'success' => false,
                'error' => 'La calificación debe ser numérica'
            ];
        }

        $calificacion = (int) $calificacion;

        if ($calificacion < 1 || $calificacion > 5) {
            return [
                'success' => false,
                'error' => 'La calificación debe estar entre 1 y 5'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida comentario
     */
    public static function validarComentario($comentario)
    {
        $comentario = trim($comentario);

        if (mb_strlen($comentario) < 3) {
            return [
                'success' => false,
                'error' => 'El comentario debe tener al menos 3 caracteres'
            ];
        }

        if (mb_strlen($comentario) > 1000) {
            return [
                'success' => false,
                'error' => 'El comentario no puede superar los 1000 caracteres'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida payload completo para crear una reseña
     *
     * Espera datos ya sanitizados.
     */
    public static function validarCrear(array $data)
    {
        $errores = [];

        $resultado = self::validarReservaId(
            $data['reserva_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['reserva_id'] = $resultado['error'];
        }

        $resultado = self::validarTipo(
            $data['tipo'] ?? null
        );

        if (!$resultado['success']) {
            $errores['tipo'] = $resultado['error'];
        }

        $resultado = self::validarCalificacion(
            $data['calificacion'] ?? null
        );

        if (!$resultado['success']) {
            $errores['calificacion'] = $resultado['error'];
        }

        if (
            isset($data['comentario']) &&
            $data['comentario'] !== null &&
            $data['comentario'] !== ''
        ) {
            $resultado = self::validarComentario(
                $data['comentario']
            );

            if (!$resultado['success']) {
                $errores['comentario'] = $resultado['error'];
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
            'message' => 'Validación exitosa',
            'errors' => null
        ];
    }

    /**
     * Valida solamente el ID de reseña
     *
     * Retorna ['success'=>bool, 'message'=>string, 'errors'=>array|null]
     */
    public static function validarSoloId($id)
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