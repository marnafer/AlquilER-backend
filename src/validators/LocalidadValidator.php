<?php

namespace App\Validators;

use App\Models\Provincia;

class LocalidadValidator
{
    public static function validarLocalidad(array $data, bool $requerirId = false): array
    {
        $errores = [];

        if ($requerirId) {
            $resultado = self::validarIdLocalidad($data['id'] ?? null);

            if (!$resultado['success']) {
                $errores['id'] = $resultado['error'];
            }
        }

        if (array_key_exists('nombre', $data)) {
            $resultado = self::validarNombreLocalidad($data['nombre'] ?? null);

            if (!$resultado['success']) {
                $errores['nombre'] = $resultado['error'];
            }
        }

        if (array_key_exists('codigo_postal', $data)) {
            $resultado = self::validarCodigoPostal($data['codigo_postal'] ?? null);

            if (!$resultado['success']) {
                $errores['codigo_postal'] = $resultado['error'];
            }
        }

        if (array_key_exists('provincia_id', $data)) {
            $resultado = self::validarProvinciaId($data['provincia_id'] ?? null);

            if (!$resultado['success']) {
                $errores['provincia_id'] = $resultado['error'];
            }
        }

        if (!empty($errores)) {
            return [
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $errores,
            ];
        }

        return [
            'success' => true,
            'message' => 'Validación exitosa',
            'errors' => null,
        ];
    }

    public static function validarIdLocalidad($id): array
    {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' => 'El ID de localidad es requerido. Debe ser un numero entero positivo.',
            ];
        }

        if (!is_numeric($id)) {
            return [
                'success' => false,
                'error' => 'El ID de localidad debe ser numérico',
            ];
        }

        if ((int) $id <= 0) {
            return [
                'success' => false,
                'error' => 'El ID de localidad debe ser positivo',
            ];
        }

        return [
            'success' => true,
            'error' => null,
        ];
    }

    public static function validarNombreLocalidad(?string $nombre): array
    {
        if ($nombre === null || $nombre === '') {
            return [
                'success' => false,
                'error' => 'El nombre es requerido',
            ];
        }

        $nombre = trim($nombre);

        if (mb_strlen($nombre) < 2) {
            return [
                'success' => false,
                'error' => 'El nombre debe tener al menos 2 caracteres',
            ];
        }

        if (mb_strlen($nombre) > 150) {
            return [
                'success' => false,
                'error' => 'El nombre no puede exceder los 150 caracteres',
            ];
        }

        if (!preg_match('/^[\pL\pN\s\-\.&()]+$/u', $nombre)) {
            return [
                'success' => false,
                'error' => 'El nombre solo puede contener letras, números, espacios, guiones y &',
            ];
        }

        return [
            'success' => true,
            'error' => null,
        ];
    }

    public static function validarCodigoPostal(?string $codigoPostal): array
    {
        if ($codigoPostal === null || $codigoPostal === '') {
            return [
                'success' => false,
                'error' => 'El código postal es requerido',
            ];
        }

        $codigoPostal = trim($codigoPostal);

        if (mb_strlen($codigoPostal) < 2) {
            return [
                'success' => false,
                'error' => 'El código postal debe tener al menos 2 caracteres',
            ];
        }

        if (mb_strlen($codigoPostal) > 20) {
            return [
                'success' => false,
                'error' => 'El código postal no puede exceder los 20 caracteres',
            ];
        }

        if (!preg_match('/^[A-Za-z0-9\-\s]+$/u', $codigoPostal)) {
            return [
                'success' => false,
                'error' => 'Formato de código postal inválido',
            ];
        }

        return [
            'success' => true,
            'error' => null,
        ];
    }

    public static function validarProvinciaId($provinciaId): array
    {
        if ($provinciaId === null || $provinciaId === '') {
            return [
                'success' => false,
                'error' => 'La provincia es requerida',
            ];
        }

        if (!is_numeric($provinciaId) || (int) $provinciaId <= 0) {
            return [
                'success' => false,
                'error' => 'Provincia inválida',
            ];
        }

        if (!Provincia::find((int) $provinciaId)) {
            return [
                'success' => false,
                'error' => 'La provincia no existe',
            ];
        }

        return [
            'success' => true,
            'error' => null,
        ];
    }

    public static function validarCrearLocalidad(array $data): array
    {
        return self::validarLocalidad($data, false);
    }

    public static function validarActualizarLocalidad(array $data): array
    {
        return self::validarLocalidad($data, true);
    }

    public static function validarSoloIdLocalidad($id): array
    {
        $resultado = self::validarIdLocalidad($id);

        if (!$resultado['success']) {
            return [
                'success' => false,
                'message' => 'ID inválido',
                'errors' => [
                    'id' => $resultado['error'],
                ],
            ];
        }

        return [
            'success' => true,
            'message' => 'ID válido',
            'errors' => null,
        ];
    }
}