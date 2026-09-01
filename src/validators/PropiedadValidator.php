<?php

namespace App\Validators;

use App\Models\Categoria;
use App\Models\Localidad;

class PropiedadValidator
{
    /**
     * Validar propiedad completa
     */
    public static function validarPropiedad(
        $data,
        $requerirId = false
    ): array {

        $errores = [];

        if ($requerirId) {

            $resultado = self::validarIdRequerido(
                $data['id'] ?? null,
                'propiedad'
            );

            if (!$resultado['success']) {
                $errores['id'] = $resultado['error'];
            }
        }

        if (
            empty($data['titulo'])
        ) {
            $errores['titulo'] =
                'El título es obligatorio';
        } elseif (
            mb_strlen($data['titulo']) > 150
        ) {
            $errores['titulo'] =
                'El título no puede superar los 150 caracteres';
        }

        if (
            empty($data['direccion'])
        ) {
            $errores['direccion'] =
                'La dirección es obligatoria';
        } elseif (
            mb_strlen($data['direccion']) > 125
        ) {
            $errores['direccion'] =
                'La dirección no puede superar los 125 caracteres';
        }

        if (
            isset($data['descripcion']) &&
            $data['descripcion'] !== null &&
            mb_strlen($data['descripcion']) > 5000
        ) {
            $errores['descripcion'] =
                'La descripción no puede superar los 5000 caracteres';
        }

        if (
            !is_numeric($data['precio'])
        ) {
            $errores['precio'] =
                'El precio es obligatorio';
        } elseif (
            $data['precio'] <= 0
        ) {
            $errores['precio'] =
                'El precio debe ser mayor a 0';
        }

        if (
            !is_numeric($data['expensas'])
        ) {
            $errores['expensas'] =
                'Las expensas son inválidas';
        } elseif (
            $data['expensas'] < 0
        ) {
            $errores['expensas'] =
                'Las expensas no pueden ser negativas';
        }

        if (
            !is_numeric($data['cantidad_ambientes']) ||
            $data['cantidad_ambientes'] < 1
        ) {
            $errores['cantidad_ambientes'] =
                'La cantidad de ambientes debe ser mayor a 0';
        }

        if (
            !is_numeric($data['cantidad_dormitorios']) ||
            $data['cantidad_dormitorios'] < 1
        ) {
            $errores['cantidad_dormitorios'] =
                'La cantidad de dormitorios debe ser mayor a 0';
        } elseif (
            isset($data['cantidad_ambientes']) &&
            is_numeric($data['cantidad_ambientes']) &&
            $data['cantidad_dormitorios'] >
            $data['cantidad_ambientes']
        ) {
            $errores['cantidad_dormitorios'] =
                'Los dormitorios no pueden superar la cantidad de ambientes';
        }

        if (
            !is_numeric($data['cantidad_banos']) ||
            $data['cantidad_banos'] < 1
        ) {
            $errores['cantidad_banos'] =
                'La cantidad de baños debe ser mayor a 0';
        } elseif (
            isset($data['cantidad_ambientes']) &&
            is_numeric($data['cantidad_ambientes']) &&
            $data['cantidad_banos'] >
            $data['cantidad_ambientes']
        ) {
            $errores['cantidad_banos'] =
                'Los baños no pueden superar la cantidad de ambientes';
        }

        if (
            isset($data['capacidad']) &&
            $data['capacidad'] !== null &&
            $data['capacidad'] <= 0
        ) {
            $errores['capacidad'] =
                'La capacidad debe ser mayor a 0';
        }

        if (
            !in_array(
                $data['disponible'],
                [0, 1],
                true
            )
        ) {
            $errores['disponible'] =
                'El estado de disponibilidad es inválido';
        }

        if (
            empty($data['categoria_id'])
        ) {

            $errores['categoria_id'] =
                'La categoría es obligatoria';

        } elseif (
            !Categoria::find(
                $data['categoria_id']
            )
        ) {

            $errores['categoria_id'] =
                'La categoría seleccionada no existe';
        }

        if (
            empty($data['localidad_id'])
        ) {

            $errores['localidad_id'] =
                'La localidad es obligatoria';

        } elseif (
            !Localidad::find(
                $data['localidad_id']
            )
        ) {

            $errores['localidad_id'] =
                'La localidad seleccionada no existe';
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
     * Validar ID requerido
     */
    public static function validarIdRequerido(
        $id,
        $campo = ''
    ): array {

        if (
            $id === null ||
            $id === ''
        ) {

            return [
                'success' => false,
                'error' =>
                    "El ID de $campo es requerido. Debe ser un entero positivo."
            ];
        }

        if (
            !is_numeric($id) ||
            $id <= 0
        ) {

            return [
                'success' => false,
                'error' =>
                    "El ID de $campo debe ser positivo"
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Crear
     */
    public static function validarCrearPropiedad(
        $data
    ): array {

        return self::validarPropiedad(
            $data,
            false
        );
    }

    /**
     * Actualizar
     */
    public static function validarActualizarPropiedad(
        $data
    ): array {

        return self::validarPropiedad(
            $data,
            true
        );
    }

    /**
     * Solo ID
     */
    public static function validarSoloIdPropiedad(
        $id
    ): array {

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
}