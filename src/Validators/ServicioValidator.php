<?php

namespace App\Validators;

class ServicioValidator
{
    public static function validarId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de servicio es requerido';
        }

        if (!is_numeric($id)) {
            return 'El ID de servicio debe ser numérico';
        }

        if ((int) $id <= 0) {
            return 'El ID de servicio debe ser mayor a cero';
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            return 'El ID de servicio debe ser un entero válido';
        }

        return null;
    }

    public static function validarNombre(
        ?string $nombre
    ): ?string {
        if ($nombre === null || $nombre === '') {
            return 'El nombre del servicio es requerido';
        }

        $len = mb_strlen($nombre);

        if ($len < 3) {
            return 'El nombre debe tener al menos 3 caracteres';
        }

        if ($len > 50) {
            return 'El nombre no puede superar los 50 caracteres';
        }

        if (!preg_match(
            '/^[\p{L}\p{N}\s\-\&]+$/u',
            $nombre
        )) {
            return 'El nombre solo puede contener letras, números, espacios, guiones y &';
        }

        return null;
    }

    public static function validar(
        array $data,
        bool $requerirId = false
    ): array {
        $errores = [];

        if ($requerirId) {
            $error = self::validarId(
                $data['id'] ?? null
            );

            if ($error) {
                $errores['id'] = $error;
            }
        }

        $error = self::validarNombre(
            $data['nombre'] ?? null
        );

        if ($error) {
            $errores['nombre'] = $error;
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