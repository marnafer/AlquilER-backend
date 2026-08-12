<?php

namespace App\Validators;

use App\Models\Provincia;

class LocalidadValidator
{
    public static function validarLocalidad($data, $requerirId = false): array
    {
        $errores = [];

        if ($requerirId) {
            $resultado = self::validarIdRequerido(
                $data['id'] ?? null,
                'localidad'
            );

            if (!$resultado['success']) {
                $errores['id'] = $resultado['error'];
            }
        }

        $resultado = self::validarNombreLocalidad(
            $data['nombre'] ?? null
        );

        if (!$resultado['success']) {
            $errores['nombre'] = $resultado['error'];
        }

        $resultado = self::validarCodigoPostal(
            $data['codigo_postal'] ?? null
        );

        if (!$resultado['success']) {
            $errores['codigo_postal'] = $resultado['error'];
        }

        $resultado = self::validarProvinciaId(
            $data['provincia_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['provincia_id'] = $resultado['error'];
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

    public static function validarIdRequerido($id, $campo = ''): array
    {
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

    public static function validarNombreLocalidad($nombre): array
    {
        if (!$nombre) {
            return [
                'success' => false,
                'error' => 'El nombre es requerido'
            ];
        }

        if (mb_strlen($nombre) < 2) {
            return [
                'success' => false,
                'error' => 'El nombre debe tener al menos 2 caracteres'
            ];
        }

        if (mb_strlen($nombre) > 150) {
            return [
                'success' => false,
                'error' => 'El nombre no puede exceder los 150 caracteres'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    public static function validarCodigoPostal($codigoPostal): array
    {
        if ($codigoPostal === null || $codigoPostal === '') {
            return [
                'success' => true,
                'error' => null
            ];
        }

        if (mb_strlen($codigoPostal) > 20) {
            return [
                'success' => false,
                'error' => 'El código postal no puede exceder los 20 caracteres'
            ];
        }

        if (!preg_match('/^[A-Za-z0-9\-\s]+$/u', $codigoPostal)) {
            return [
                'success' => false,
                'error' => 'Formato de código postal inválido'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    public static function validarProvinciaId($provinciaId): array
    {
        if ($provinciaId === null || $provinciaId === '') {
            return [
                'success' => false,
                'error' => 'La provincia es requerida'
            ];
        }

        if (!is_numeric($provinciaId) || $provinciaId <= 0) {
            return [
                'success' => false,
                'error' => 'Provincia inválida'
            ];
        }

        if (!Provincia::find($provinciaId)) {
            return [
                'success' => false,
                'error' => 'La provincia no existe'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    public static function validarCrearLocalidad($data): array
    {
        return self::validarLocalidad($data, false);
    }

    public static function validarActualizarLocalidad($data): array
    {
        return self::validarLocalidad($data, true);
    }

    public static function validarSoloIdLocalidad($id): array
    {
        $resultado = self::validarIdRequerido(
            $id,
            'localidad'
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