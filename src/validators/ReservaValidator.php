<?php

namespace App\Validators;

class ReservaValidator
{
    /**
     * Validar reserva completa
     */
    public static function validarReserva(
        array $data,
        bool $requerirId = false
    ): array {

        $errores = [];

        // ID
        if ($requerirId) {

            $resultado = self::validarIdRequerido(
                $data['id'] ?? null,
                'reserva'
            );

            if (!$resultado['success']) {
                $errores['id'] = $resultado['error'];
            }
        }

        // Propiedad
        $resultado = self::validarPropiedadId(
            $data['propiedad_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['propiedad_id'] = $resultado['error'];
        }

        // Usuario
        $resultado = self::validarUsuarioId(
            $data['usuario_id'] ?? null
        );

        if (!$resultado['success']) {
            $errores['usuario_id'] = $resultado['error'];
        }

        // Fecha inicio
        $resultado = self::validarFechaInicio(
            $data['fecha_inicio_alquiler'] ?? null
        );

        if (!$resultado['success']) {
            $errores['fecha_inicio_alquiler'] = $resultado['error'];
        }

        // Fecha fin (opcional)
        if (
            isset($data['fecha_fin_alquiler']) &&
            $data['fecha_fin_alquiler'] !== null
        ) {

            $resultado = self::validarFechaFin(
                $data['fecha_fin_alquiler']
            );

            if (!$resultado['success']) {
                $errores['fecha_fin_alquiler'] = $resultado['error'];
            }
        }

        // Relación entre fechas
        if (
            !isset($errores['fecha_inicio_alquiler']) &&
            !isset($errores['fecha_fin_alquiler']) &&
            !empty($data['fecha_fin_alquiler'])
        ) {

            $resultado = self::validarRelacionFechas(
                $data['fecha_inicio_alquiler'],
                $data['fecha_fin_alquiler']
            );

            if (!$resultado['success']) {
                $errores['fecha_fin_alquiler'] = $resultado['error'];
            }
        }

        // Estado
        if (
            isset($data['estado']) &&
            $data['estado'] !== null
        ) {

            $resultado = self::validarEstado(
                $data['estado']
            );

            if (!$resultado['success']) {
                $errores['estado'] = $resultado['error'];
            }
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
     * ID requerido
     */
    public static function validarIdRequerido(
        $id,
        string $campo = ''
    ): array {

        if ($id === null || $id === '') {

            return [
                'success' => false,
                'error' => "El ID de $campo es requerido. Debe ser un entero positivo."
            ];
        }

        if (!is_numeric($id) || $id <= 0) {

            return [
                'success' => false,
                'error' => "El ID de $campo debe ser un numero entero positivo"
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Propiedad
     */
    public static function validarPropiedadId(
        $id
    ): array {

        if ($id === null || $id === '') {

            return [
                'success' => false,
                'error' => 'El ID de propiedad es requerido. Debe ser un entero positivo.'
            ];
        }

        if (!is_numeric($id) || $id <= 0) {

            return [
                'success' => false,
                'error' => 'El ID de propiedad debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Usuario
     */
    public static function validarUsuarioId(
        $id
    ): array {

        if ($id === null || $id === '') {

            return [
                'success' => false,
                'error' => 'El ID de usuario es requerido. Debe ser un entero positivo.'
            ];
        }

        if (!is_numeric($id) || $id <= 0) {

            return [
                'success' => false,
                'error' => 'El ID de usuario debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Fecha inicio
     */
    public static function validarFechaInicio(
        $fecha
    ): array {

        if ($fecha === null || $fecha === '') {

            return [
                'success' => false,
                'error' => 'La fecha de inicio es requerida'
            ];
        }

        if (!strtotime($fecha)) {

            return [
                'success' => false,
                'error' => 'Fecha de inicio inválida'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Fecha fin
     */
    public static function validarFechaFin(
        $fecha
    ): array {

        if (!strtotime($fecha)) {

            return [
                'success' => false,
                'error' => 'Fecha de fin inválida'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Fecha fin > fecha inicio
     */
    public static function validarRelacionFechas(
        string $inicio,
        string $fin
    ): array {

        if (strtotime($fin) <= strtotime($inicio)) {

            return [
                'success' => false,
                'error' => 'La fecha de fin debe ser posterior a la fecha de inicio'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Estado
     */
    public static function validarEstado(
        $estado
    ): array {

        $validos = [
            'pendiente',
            'aprobada',
            'rechazada',
            'cancelada',
            'finalizada'
        ];

        if (!in_array($estado, $validos, true)) {

            return [
                'success' => false,
                'error' => 'Estado inválido'
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
    public static function validarCrear(
        array $data
    ): array {

        return self::validarReserva(
            $data,
            false
        );
    }

    /**
     * Actualizar
     */
    public static function validarActualizar(
        array $data
    ): array {

        return self::validarReserva(
            $data,
            true
        );
    }

    /**
     * Solo ID
     */
    public static function validarSoloId(
        $id
    ): array {

        $resultado = self::validarIdRequerido(
            $id,
            'reserva'
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
     * Solo estado
     */
    public static function validarSoloEstado(
        $estado
    ): array {

        $resultado = self::validarEstado(
            $estado
        );

        if (!$resultado['success']) {

            return [
                'success' => false,
                'message' => 'Estado inválido',
                'errors' => [
                    'estado' => $resultado['error']
                ]
            ];
        }

        return [
            'success' => true,
            'message' => 'Estado válido',
            'errors' => null
        ];
    }
}