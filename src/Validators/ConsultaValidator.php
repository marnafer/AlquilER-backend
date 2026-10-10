<?php

namespace App\Validators;

class ConsultaValidator
{
    /**
     * Validar consulta completa
     */
    public static function validarConsulta(
        $data,
        $requerirId = false
    ): array {

        $errores = [];

        // ID
        if ($requerirId) {

            $resultado = self::validarIdRequerido(
                $data['id'] ?? null,
                'consulta'
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

        // Usuario ID
        if (
            isset($data['usuario_id'])
            && $data['usuario_id'] !== null
        ) {

            $resultado = self::validarUsuarioId(
                $data['usuario_id']
            );

            if (!$resultado['success']) {
                $errores['usuario_id'] = $resultado['error'];
            }
        }
        
        // Mensaje
        if (
            isset($data['mensaje'])
            && $data['mensaje'] !== null
        ) {
            $resultado = self::validarMensajeConsulta(
                $data['mensaje']
            );

            if (!$resultado['success']) {
                $errores['mensaje'] = $resultado['error'];
            }
        }

        // Fecha opcional
        if (
            isset($data['fecha_consulta'])
            && $data['fecha_consulta'] !== null
        ) {

            $resultado = self::validarFechaConsulta(
                $data['fecha_consulta']
            );

            if (!$resultado['success']) {
                $errores['fecha_consulta'] = $resultado['error'];
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
     * Validar ID requerido
     */
    public static function validarIdRequerido(
        $id,
        $campo = ''
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
                'error' => "El ID de $campo debe ser positivo"
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Validar propiedad_id
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
     * Validar usuario_id
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
     * Validar mensaje
     */
    public static function validarMensajeConsulta(
        $mensaje
    ): array {

        if ($mensaje === null || $mensaje === '') {

            return [
                'success' => false,
                'error' => 'El mensaje es requerido'
            ];
        }

        $longitud = mb_strlen(trim($mensaje));

        if ($longitud < 5) {

            return [
                'success' => false,
                'error' => 'El mensaje debe tener al menos 5 caracteres'
            ];
        }

        if ($longitud > 5000) {

            return [
                'success' => false,
                'error' => 'El mensaje no puede superar los 5000 caracteres'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Validar fecha
     */
    public static function validarFechaConsulta(
        $fecha
    ): array {

        $timestamp = strtotime($fecha);

        if (!$timestamp) {

            return [
                'success' => false,
                'error' => 'Fecha inválida'
            ];
        }

        if ($timestamp > time()) {

            return [
                'success' => false,
                'error' => 'La fecha no puede ser futura'
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
    public static function validarCrearConsulta(
        $data
    ): array {

        $resultado = self::validarConsulta(
            $data,
            false
        );

        $erroresPerfil = self::validarPerfilInteresado(
            $data['perfil_interesado'] ?? null
        );

        if ($erroresPerfil !== []) {
            $resultado['success'] = false;
            $resultado['message'] = 'Error de validación';
            $resultado['errors'] = array_merge(
                $resultado['errors'] ?? [],
                ['perfil_interesado' => $erroresPerfil]
            );
        }

        return $resultado;
    }

    public static function validarPerfilInteresado($perfil): array
    {
        if ($perfil === null) {
            return [];
        }

        if (!is_array($perfil)) {
            return ['Los datos del interesado deben enviarse como un objeto'];
        }

        $errores = [];
        $fecha = $perfil['fecha_mudanza'] ?? null;
        $date = is_string($fecha)
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha)
            : false;
        if (!$date || $date->format('Y-m-d') !== $fecha) {
            $errores[] = 'Indicá una fecha de mudanza válida';
        }

        $ocupantes = $perfil['cantidad_ocupantes'] ?? null;
        if (
            filter_var($ocupantes, FILTER_VALIDATE_INT) === false
            || (int) $ocupantes < 1
            || (int) $ocupantes > 50
        ) {
            $errores[] = 'La cantidad de ocupantes debe ser un entero entre 1 y 50';
        }

        $mascotas = filter_var(
            $perfil['tiene_mascotas'] ?? null,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
        if ($mascotas === null) {
            $errores[] = 'Indicá si tenés mascotas';
        }

        $cantidadMascotas = $perfil['cantidad_mascotas'] ?? null;
        if (
            filter_var($cantidadMascotas, FILTER_VALIDATE_INT) === false
            || (int) $cantidadMascotas < 0
            || (int) $cantidadMascotas > 20
            || ($mascotas && (int) $cantidadMascotas < 1)
            || (!$mascotas && (int) $cantidadMascotas !== 0)
        ) {
            $errores[] = 'La cantidad de mascotas no coincide con la respuesta indicada';
        }

        $garantias = $perfil['garantias'] ?? null;
        $garantiasPermitidas = [
            'recibo_sueldo',
            'garantia_propietaria',
            'seguro_caucion',
            'garante',
        ];
        if (!is_array($garantias)) {
            $errores[] = 'Las garantías disponibles deben enviarse como una lista';
        } else {
            foreach ($garantias as $garantia) {
                if (!in_array($garantia, $garantiasPermitidas, true)) {
                    $errores[] = 'La lista contiene una garantía no válida';
                    break;
                }
            }
        }

        return $errores;
    }

    /**
     * Actualizar
     */
    public static function validarActualizarConsulta(
        $data
    ): array {

        $errores = [];

        // ID
        $resultado = self::validarIdRequerido(
            $data['id'] ?? null,
            'consulta'
        );

        if (!$resultado['success']) {
            $errores['id'] = $resultado['error'];
        }

        // Mensaje
        $resultado = self::validarMensajeConsulta(
            $data['mensaje'] ?? null
        );

        if (!$resultado['success']) {
            $errores['mensaje'] = $resultado['error'];
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
     * Validar solo ID
     */
    public static function validarSoloIdConsulta(
        $id
    ): array {

        $resultado = self::validarIdRequerido(
            $id,
            'consulta'
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