<?php

declare(strict_types=1);

namespace App\Validators;

class FavoritoValidator
{
    /**
     * Validar datos para crear un favorito.
     */
    public static function validarCrear(array $data): array
    {
        $errores = [];

        $resultado = self::validarPropiedadId(
            $data['propiedad_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['propiedad_id'] = $resultado['error'];
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
     * Validar únicamente un ID.
     */
    public static function validarSoloId($id): array
    {
        $resultado = self::validarIdRequerido(
            $id,
            'propiedad'
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

    /**
     * Validar ID de propiedad.
     */
    public static function validarPropiedadId(
        $propiedadId
    ): array {
        return self::validarIdRequerido(
            $propiedadId,
            'propiedad'
        );
    }

    /**
     * Validar que un ID sea obligatorio y positivo.
     */
    public static function validarIdRequerido(
        $id,
        string $campo = ''
    ): array {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' =>
                    "El ID de $campo es requerido. Debe ser un entero positivo"
            ];
        }

        if (
            !is_int($id) ||
            $id <= 0
        ) {
            return [
                'success' => false,
                'error' =>
                    "El ID de $campo debe ser un entero positivo"
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }
}