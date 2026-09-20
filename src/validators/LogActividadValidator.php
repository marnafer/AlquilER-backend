<?php

namespace App\Validators;

class LogActividadValidator
{
    public static function validar(array $data): array
    {
        $errores = [];

        $error = self::validarUsuarioId($data['usuario_id'] ?? null);
        if ($error) {
            $errores['usuario_id'] = $error;
        }

        $error = self::validarAccion($data['accion'] ?? null);
        if ($error) {
            $errores['accion'] = $error;
        }

        if (
            isset($data['ip_address']) &&
            $data['ip_address'] !== null &&
            $data['ip_address'] !== ''
        ) {
            $error = self::validarIp($data['ip_address']);

            if ($error) {
                $errores['ip_address'] = $error;
            }
        }

        return [
            'success' => empty($errores),
            'message' => empty($errores)
                ? 'Validación exitosa'
                : 'Error de validación',
            'errors' => empty($errores) ? null : $errores
        ];
    }

    /**
     * Validar ID
     */
    public static function validarId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID es requerido. Debe ser un entero positivo.';
        }

        if (!is_numeric($id)) {
            return 'El ID debe ser numérico';
        }

        if ((int)$id <= 0) {
            return 'El ID debe ser mayor a 0';
        }

        return null;
    }

    /**
     * Validar usuario_id
     */
    public static function validarUsuarioId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de usuario es requerido. Debe ser un entero positivo.';
        }

        if (!is_numeric($id)) {
            return 'El ID de usuario debe ser un número';
        }

        if ((int)$id <= 0) {
            return 'El ID de usuario debe ser un número positivo';
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            return 'El ID de usuario debe ser un número entero';
        }

        return null;
    }

    /**
     * Validar acción
     */
    public static function validarAccion($accion): ?string
    {
        if ($accion === null || $accion === '') {
            return 'La acción es obligatoria';
        }

        $accion = trim($accion);

        if (mb_strlen($accion) < 3) {
            return 'La acción debe tener al menos 3 caracteres';
        }

        if (mb_strlen($accion) > 255) {
            return 'La acción no puede superar los 255 caracteres';
        }

        return null;
    }

    /**
     * Validar IP
     */
    public static function validarIp($ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return 'La IP no es válida';
        }

        return null;
    }

    /**
     * Validar solo ID (para rutas)
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