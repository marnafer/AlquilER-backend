<?php

namespace App\Validators;

class ResenaValidator
{
    /**
     * Validación para crear reseña
     */
    public static function validarCrear(array $data): array
    {
        $errores = [];

        $error = self::validarReservaId(
            $data['reserva_id'] ?? null
        );

        if ($error) {
            $errores['reserva_id'] = $error;
        }

        $error = self::validarCalificacion(
            $data['calificacion'] ?? null
        );

        if ($error) {
            $errores['calificacion'] = $error;
        }

        if (
            isset($data['comentario']) &&
            $data['comentario'] !== null &&
            $data['comentario'] !== ''
        ) {
            $error = self::validarComentario(
                $data['comentario']
            );

            if ($error) {
                $errores['comentario'] = $error;
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
     * Validación para actualizar reseña
     */
    public static function validarActualizar(array $data): array
    {
        $errores = [];

        $error = self::validarId(
            $data['id'] ?? null
        );

        if ($error) {
            $errores['id'] = $error;
        }

        $error = self::validarCalificacion(
            $data['calificacion'] ?? null
        );

        if ($error) {
            $errores['calificacion'] = $error;
        }

        if (
            isset($data['comentario']) &&
            $data['comentario'] !== null &&
            $data['comentario'] !== ''
        ) {
            $error = self::validarComentario(
                $data['comentario']
            );

            if ($error) {
                $errores['comentario'] = $error;
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
     * Validar ID
     */
    public static function validarId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de reseña es requerido. Debe ser un numero entero positivo.';
        }

        if (!is_numeric($id)) {
            return 'El ID debe ser numérico';
        }

        if ((int)$id <= 0) {
            return 'El ID debe ser positivo';
        }

        return null;
    }

    /**
     * Validar reserva_id
     */
    public static function validarReservaId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de reserva es requerido. Debe ser un numero entero positivo.';
        }

        if (!is_numeric($id)) {
            return 'El ID de reserva debe ser numérico';
        }

        if ((int)$id <= 0) {
            return 'El ID de reserva debe ser positivo';
        }

        return null;
    }

    /**
     * Validar calificación
     */
    public static function validarCalificacion(
        $calificacion
    ): ?string {
        if (
            $calificacion === null ||
            $calificacion === ''
        ) {
            return 'La calificación es requerida';
        }

        if (!is_numeric($calificacion)) {
            return 'La calificación debe ser numérica';
        }

        $calificacion = (int)$calificacion;

        if (
            $calificacion < 1 ||
            $calificacion > 5
        ) {
            return 'La calificación debe estar entre 1 y 5';
        }

        return null;
    }

    /**
     * Validar comentario
     */
    public static function validarComentario(
        $comentario
    ): ?string {
        $comentario = trim($comentario);

        if (mb_strlen($comentario) < 3) {
            return 'El comentario debe tener al menos 3 caracteres';
        }

        if (mb_strlen($comentario) > 1000) {
            return 'El comentario no puede superar los 1000 caracteres';
        }

        return null;
    }

    /**
     * Validar solo ID
     */
    public static function validarSoloId(
        $id
    ): array {
        $error = self::validarId($id);

        if ($error) {
            return [
                'success' => false,
                'message' => 'ID inválido',
                'errors' => [
                    'id' => $error
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