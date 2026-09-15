<?php

namespace App\Validators;

class PropiedadServicioValidator
{
    public static function validar(array $data, bool $requerirId = false): array
    {
        $errores = [];

        if ($requerirId) {
            $error = self::validarId($data['id'] ?? null);

            if ($error) {
                $errores['id'] = $error;
            }
        }

        $error = self::validarPropiedadId($data['propiedad_id'] ?? null);

        if ($error) {
            $errores['propiedad_id'] = $error;
        }

        $error = self::validarServicioId($data['servicio_id'] ?? null);

        if ($error) {
            $errores['servicio_id'] = $error;
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

    public static function validarId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de la relación es requerido';
        }

        if (!is_numeric($id)) {
            return 'El ID debe ser numérico';
        }

        if ((int) $id <= 0) {
            return 'El ID debe ser mayor a cero';
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            return 'El ID debe ser un entero válido';
        }

        return null;
    }

    public static function validarPropiedadId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de propiedad es requerido. Debe ser un entero positivo.';
        }

        if (!is_numeric($id)) {
            return 'El ID de propiedad debe ser numérico';
        }

        if ((int) $id <= 0) {
            return 'El ID de propiedad debe ser mayor a cero';
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            return 'El ID de propiedad debe ser un entero válido';
        }

        return null;
    }

    public static function validarServicioId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de servicio es requerido. Debe ser un entero positivo.';
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

    public static function validarServicioIds(array $ids, bool $permitirVacio = false): array
    {
        $errores = [];

        if (!$permitirVacio && empty($ids)) {
            return [
                'success' => false,
                'message' => 'Error de validación',
                'errors' => [
                    'servicio_ids' => 'Debe proporcionar al menos un ID de servicio'
                ]
            ];
        }

        foreach ($ids as $indice => $id) {
            $error = self::validarServicioId($id);

            if ($error) {
                $errores["servicio_ids.{$indice}"] = $error;
            }
        }

        if (count($ids) !== count(array_unique(array_map('strval', $ids)))) {
            $errores['servicio_ids'] = 'No se permiten IDs de servicio duplicados';
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

    public static function validarCrear(array $data): array
    {
        return self::validar($data, false);
    }

    public static function validarActualizar(array $data): array
    {
        return self::validar($data, true);
    }

    public static function validarSoloId($id): array
    {
        $error = self::validarId($id);

        if ($error) {
            return [
                'success' => false,
                'message' => 'ID inválido',
                'errors' => ['id' => $error]
            ];
        }

        return [
            'success' => true,
            'message' => 'ID válido',
            'errors' => null
        ];
    }
}