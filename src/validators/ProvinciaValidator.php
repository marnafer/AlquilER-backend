<?php

namespace App\Validators;

class ProvinciaValidator
{
    /**
     * Validar ID.
     */
    public static function validarId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de provincia es requerido';
        }

        if (!is_numeric($id)) {
            return 'El ID de provincia debe ser numérico';
        }

        if ((int) $id <= 0) {
            return 'El ID de provincia debe ser positivo';
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            return 'El ID de provincia debe ser un número entero';
        }

        return null;
    }

    /**
     * Validar nombre.
     */
    public static function validarNombre(?string $nombre): ?string
    {
        if ($nombre === null || $nombre === '') {
            return 'El nombre es requerido';
        }

        $nombre = trim($nombre);

        if (mb_strlen($nombre) < 3) {
            return 'El nombre debe tener al menos 3 caracteres';
        }

        if (mb_strlen($nombre) > 100) {
            return 'El nombre no puede exceder los 100 caracteres';
        }

        if (!preg_match('/^[\p{L}\s]+$/u', $nombre)) {
            return 'El nombre solo puede contener letras y espacios';
        }

        return null;
    }

    /**
     * Validar datos de provincia.
     */
    public static function validar(array $data): array
    {
        $errores = [];

        $error = self::validarNombre(
            $data['nombre'] ?? null
        );

        if ($error) {
            $errores['nombre'] = $error;
        }

        return [
            'success' => empty($errores),
            'message' => empty($errores)
                ? 'Validación exitosa'
                : 'Error de validación',
            'errors' => empty($errores)
                ? null
                : $errores
        ];
    }

    /**
     * Validar solo ID.
     */
    public static function validarSoloId($id): array
    {
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