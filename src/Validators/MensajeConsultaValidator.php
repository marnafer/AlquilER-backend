<?php

namespace App\Validators;

class MensajeConsultaValidator
{
    /**
     * Validar mensaje completo
     */
    public static function validarMensajeConsulta(
        $data,
        $requerirId = false
    ): array {
        $errores = [];

        $id = $data['id'] ?? null;
        $consultaId = $data['consulta_id'] ?? null;
        $mensaje = $data['mensaje'] ?? null;

        if ($requerirId) {
            $resultado = self::validarIdRequerido($id, 'mensaje');

            if (!$resultado['success']) {
                $errores['id'] = $resultado['error'];
            }
        }

        if (empty($consultaId)) {
            $errores['consulta_id'] = 'El ID de la consulta es obligatorio';
        } elseif (!is_numeric($consultaId) || $consultaId <= 0) {
            $errores['consulta_id'] = 'El ID de la consulta debe ser un entero positivo';
        }

        if (empty($mensaje)) {
            $errores['mensaje'] = 'El mensaje es obligatorio';
        } else {
            $longitud = mb_strlen(trim($mensaje));

            if ($longitud < 5) {
                $errores['mensaje'] = 'El mensaje debe tener al menos 5 caracteres';
            } elseif ($longitud > 2000) {
                $errores['mensaje'] = 'El mensaje no puede superar los 2000 caracteres';
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
                'error' => "El ID de $campo es requerido. Debe ser un entero positivo."
            ];
        }

        if (
            !is_numeric($id) ||
            $id <= 0
        ) {
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

    /**
     * Crear
     */
    public static function validarCrearMensajeConsulta(
        $data
    ): array {

        return self::validarMensajeConsulta(
            $data,
            false
        );
    }

    /**
     * Actualizar (Si en el futuro permites editar mensajes)
     */
    public static function validarActualizarMensajeConsulta(
        $data
    ): array {

        return self::validarMensajeConsulta(
            $data,
            true
        );
    }

    /**
     * Solo ID
     */
    public static function validarSoloIdMensajeConsulta(
        $id
    ): array {

        $resultado = self::validarIdRequerido(
            $id,
            'mensaje'
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
     * Validar parámetros de paginación del historial de mensajes.
     */
    public static function validarPaginacion(array $data): array
    {
        $errores = [];

        $limite = $data['limite'] ?? 10;

        if (
            !is_scalar($limite)
            || filter_var($limite, FILTER_VALIDATE_INT) === false
            || (int) $limite < 1
            || (int) $limite > 50
        ) {
            $errores['limite'] =
                'El límite debe ser un entero entre 1 y 50';
        }

        foreach (['antes_de_id', 'despues_de_id'] as $campo) {
            if (!array_key_exists($campo, $data)) {
                continue;
            }

            $valor = $data[$campo];

            if (
                !is_scalar($valor)
                || filter_var($valor, FILTER_VALIDATE_INT) === false
                || (int) $valor <= 0
            ) {
                $errores[$campo] = 'El ID debe ser un entero positivo';
            }
        }

        if (
            array_key_exists('antes_de_id', $data)
            && array_key_exists('despues_de_id', $data)
        ) {
            $errores['cursor'] =
                'No se pueden utilizar antes_de_id y despues_de_id juntos.';
        }

        return [
            'success' => empty($errores),
            'message' => empty($errores)
                ? 'Validación exitosa'
                : 'Error de validación',
            'errors' => empty($errores) ? null : $errores,
        ];
    }
}