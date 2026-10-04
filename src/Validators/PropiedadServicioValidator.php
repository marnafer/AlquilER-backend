<?php

namespace App\Validators;

class PropiedadServicioValidator
{
    public static function validar(
        $data,
        $requerirId = false
    ): array {

        $errores = [];

        // ID de la relación
        if ($requerirId) {

            $resultado = self::validarIdRequerido(
                $data['id'] ?? null,
                'relación'
            );

            if (!$resultado['success']) {
                $errores['id'] = $resultado['error'];
            }
        }

        // Propiedad ID
        $resultado = self::validarPropiedadId(
            $data['propiedad_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['propiedad_id'] = $resultado['error'];
        }

        // Servicio ID
        $resultado = self::validarServicioId(
            $data['servicio_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['servicio_id'] = $resultado['error'];
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

    public static function validarIdRequerido(
        $id,
        $campo = ''
    ): array {

        if ($id === null || $id === '') {

            return [
                'success' => false,
                'error' =>
                    "El ID de $campo es requerido. Debe ser un entero positivo."
            ];
        }

        if (!is_numeric($id) || $id <= 0) {

            return [
                'success' => false,
                'error' =>
                    "El ID de $campo debe ser positivo"
            ];
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {

            return [
                'success' => false,
                'error' =>
                    "El ID de $campo debe ser un entero válido"
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    public static function validarPropiedadId(
        $id
    ): array {

        if ($id === null || $id === '') {

            return [
                'success' => false,
                'error' =>
                    'El ID de propiedad es requerido. Debe ser un entero positivo.'
            ];
        }

        if (!is_numeric($id) || $id <= 0) {

            return [
                'success' => false,
                'error' =>
                    'El ID de propiedad debe ser positivo'
            ];
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {

            return [
                'success' => false,
                'error' =>
                    'El ID de propiedad debe ser un entero válido'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    public static function validarServicioId(
        $id
    ): array {

        if ($id === null || $id === '') {

            return [
                'success' => false,
                'error' =>
                    'El ID de servicio es requerido. Debe ser un entero positivo.'
            ];
        }

        if (!is_numeric($id) || $id <= 0) {

            return [
                'success' => false,
                'error' =>
                    'El ID de servicio debe ser positivo'
            ];
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {

            return [
                'success' => false,
                'error' =>
                    'El ID de servicio debe ser un entero válido'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    public static function validarServicioIds(
        array $ids,
        bool $permitirVacio = false
    ): array {

        $errores = [];

        if (!$permitirVacio && empty($ids)) {

            return [
                'success' => false,
                'message' => 'Error de validación',
                'errors' => [
                    'servicio_ids' =>
                        'Debe proporcionar al menos un ID de servicio'
                ]
            ];
        }

        foreach ($ids as $indice => $id) {

            $resultado = self::validarServicioId($id);

            if (!$resultado['success']) {
                $errores[
                    "servicio_ids.{$indice}"
                ] = $resultado['error'];
            }
        }

        if (
            count($ids) !==
            count(array_unique(array_map('strval', $ids)))
        ) {

            $errores['servicio_ids'] =
                'No se permiten IDs de servicio duplicados';
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

    public static function validarCrear(
        $data
    ): array {

        return self::validar(
            $data,
            false
        );
    }

    public static function validarActualizar(
        $data
    ): array {

        return self::validar(
            $data,
            true
        );
    }

    public static function validarSoloId(
        $id
    ): array {

        $resultado = self::validarIdRequerido(
            $id,
            'relación'
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