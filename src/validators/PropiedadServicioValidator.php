<?php

namespace App\Validators;

class PropiedadServicioValidator
{
    /**
     * Validación principal (crear / update)
     */
    public static function validar(array $data, bool $requerirId = false): array
    {
        $errores = [];

        // -------------------------
        // ID relación (solo update)
        // -------------------------
        if ($requerirId) {
            $error = self::validarId($data['id'] ?? null);

            if ($error) {
                $errores['id'] = $error;
            }
        }

        // -------------------------
        // propiedad_id
        // -------------------------
        $error = self::validarPropiedadId($data['propiedad_id'] ?? null);

        if ($error) {
            $errores['propiedad_id'] = $error;
        }

        // -------------------------
        // servicio_id
        // -------------------------
        $error = self::validarServicioId($data['servicio_id'] ?? null);

        if ($error) {
            $errores['servicio_id'] = $error;
        }

        // -------------------------
        // respuesta final
        // -------------------------
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
     * Validar ID de relación
     */
    public static function validarId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de la relación es requerido';
        }

        if (!is_numeric($id)) {
            return 'El ID debe ser numérico';
        }

        if ((int)$id <= 0) {
            return 'El ID debe ser mayor a cero';
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            return 'El ID debe ser un entero válido';
        }

        return null;
    }

    /**
     * Validar propiedad_id
     */
    public static function validarPropiedadId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de propiedad es requerido. Debe ser un entero positivo.';
        }

        if (!is_numeric($id)) {
            return 'El ID de propiedad debe ser numérico';
        }

        if ((int)$id <= 0) {
            return 'El ID de propiedad debe ser mayor a cero';
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            return 'El ID de propiedad debe ser un entero válido';
        }

        return null;
    }

    /**
     * Validar servicio_id
     */
    public static function validarServicioId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de servicio es requerido. Debe ser un entero positivo.';
        }

        if (!is_numeric($id)) {
            return 'El ID de servicio debe ser numérico';
        }

        if ((int)$id <= 0) {
            return 'El ID de servicio debe ser mayor a cero';
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            return 'El ID de servicio debe ser un entero válido';
        }

        return null;
    }

    /**
     * Validación para creación
     */
    public static function validarCrear(array $data): array
    {
        return self::validar($data, false);
    }

    /**
     * Validación para actualización
     */
    public static function validarActualizar(array $data): array
    {
        return self::validar($data, true);
    }

    /**
     * Validación rápida solo ID
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