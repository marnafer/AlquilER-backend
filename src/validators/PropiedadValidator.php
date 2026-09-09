<?php

namespace App\Validators;

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

        $id = $data['id'] ?? null;
        $titulo = $data['titulo'] ?? null;
        $direccion = $data['direccion'] ?? null;
        $descripcion = $data['descripcion'] ?? null;
        $precio = $data['precio'] ?? null;
        $expensas = $data['expensas'] ?? null;
        $cantidadAmbientes = $data['cantidad_ambientes'] ?? null;
        $cantidadDormitorios = $data['cantidad_dormitorios'] ?? null;
        $cantidadBanos = $data['cantidad_banos'] ?? null;
        $capacidad = $data['capacidad'] ?? null;
        $disponible = $data['disponible'] ?? null;
        $categoriaId = $data['categoria_id'] ?? null;
        $localidadId = $data['localidad_id'] ?? null;

        if ($requerirId) {
            $resultado = self::validarIdRequerido($id, 'propiedad');

            if (!$resultado['success']) {
                $errores['id'] = $resultado['error'];
            }
        }

        if (empty($titulo)) {
            $errores['titulo'] = 'El título es obligatorio';
        } elseif (mb_strlen($titulo) > 150) {
            $errores['titulo'] =
                'El título no puede superar los 150 caracteres';
        }

        if (empty($direccion)) {
            $errores['direccion'] = 'La dirección es obligatoria';
        } elseif (mb_strlen($direccion) > 125) {
            $errores['direccion'] =
                'La dirección no puede superar los 125 caracteres';
        }

        if (
            $descripcion !== null &&
            mb_strlen($descripcion) > 5000
        ) {
            $errores['descripcion'] =
                'La descripción no puede superar los 5000 caracteres';
        }

        if (!is_numeric($precio)) {
            $errores['precio'] = 'El precio es obligatorio';
        } elseif ($precio <= 0) {
            $errores['precio'] =
                'El precio debe ser mayor a 0';
        }

        if (!is_numeric($expensas)) {
            $errores['expensas'] = 'Las expensas son inválidas';
        } elseif ($expensas < 0) {
            $errores['expensas'] =
                'Las expensas no pueden ser negativas';
        }

        if (
            !is_numeric($cantidadAmbientes) ||
            $cantidadAmbientes < 1
        ) {
            $errores['cantidad_ambientes'] =
                'La cantidad de ambientes debe ser mayor a 0';
        }

        if (
            !is_numeric($cantidadDormitorios) ||
            $cantidadDormitorios < 1
        ) {
            $errores['cantidad_dormitorios'] =
                'La cantidad de dormitorios debe ser mayor a 0';
        } elseif (
            is_numeric($cantidadAmbientes) &&
            $cantidadDormitorios > $cantidadAmbientes
        ) {
            $errores['cantidad_dormitorios'] =
                'Los dormitorios no pueden superar la cantidad de ambientes';
        }

        if (
            !is_numeric($cantidadBanos) ||
            $cantidadBanos < 1
        ) {
            $errores['cantidad_banos'] =
                'La cantidad de baños debe ser mayor a 0';
        } elseif (
            is_numeric($cantidadAmbientes) &&
            $cantidadBanos > $cantidadAmbientes
        ) {
            $errores['cantidad_banos'] =
                'Los baños no pueden superar la cantidad de ambientes';
        }

        if (
            $capacidad !== null &&
            (!is_numeric($capacidad) || $capacidad <= 0)
        ) {
            $errores['capacidad'] =
                'La capacidad debe ser mayor a 0';
        }

        if (!in_array($disponible, [0, 1], true)) {
            $errores['disponible'] =
                'El estado de disponibilidad es inválido';
        }

        if (empty($categoriaId)) {
            $errores['categoria_id'] =
                'La categoría es obligatoria';
        }

        if (empty($localidadId)) {
            $errores['localidad_id'] =
                'La localidad es obligatoria';
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