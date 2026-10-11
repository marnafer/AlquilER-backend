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

        // Perfil opcional del interesado
        if (
            array_key_exists('perfil_interesado', $data)
            && $data['perfil_interesado'] !== null
        ) {
            $erroresPerfil = self::validarPerfilInteresado(
                $data['perfil_interesado']
            );

            if (!empty($erroresPerfil)) {
                $errores['perfil_interesado'] = $erroresPerfil;
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

            if (
            $id === null
            || $id === ''
            || filter_var($id, FILTER_VALIDATE_INT) === false
            || (int) $id <= 0
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
     * Validar propiedad_id
     */
    public static function validarPropiedadId(
        $id
    ): array {

            if (
            $id === null
            || $id === ''
            || filter_var($id, FILTER_VALIDATE_INT) === false
            || (int) $id <= 0
        ) {
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
    public static function validarUsuarioId($id): array
    {
        if (
            $id === null
            || $id === ''
            || filter_var($id, FILTER_VALIDATE_INT) === false
            || (int) $id <= 0
        ) {
            return [
                'success' => false,
                'error' => 'El ID de usuario debe ser un entero positivo'
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

        return self::validarConsulta(
            $data,
            false
        );
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

    /**
     * Validar el perfil de precalificación del interesado.
     *
     * Devuelve un array vacío si es válido,
     * o un array con los errores encontrados.
     */
    public static function validarPerfilInteresado($perfil): array
    {
        if (!is_array($perfil)) {
            return ['El perfil del interesado debe ser un objeto'];
        }

        $errores = [];

        // Fecha de mudanza: obligatoria en un perfil enviado.
        $fecha = $perfil['fecha_mudanza'] ?? null;
        $date = is_string($fecha)
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha)
            : false;
        $erroresFecha = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($erroresFecha !== false
                && (
                    $erroresFecha['warning_count'] > 0
                    || $erroresFecha['error_count'] > 0
                ))
            || $date->format('Y-m-d') !== $fecha
        ) {
            $errores['fecha_mudanza'] = 'Debe ser una fecha válida en formato YYYY-MM-DD';
        }

        $fechaMudanza = new \DateTimeImmutable(
            $perfil['fecha_mudanza']
        );

        $hoy = new \DateTimeImmutable('today');

        if ($fechaMudanza < $hoy) {
            $errores['fecha_mudanza'] = 'La fecha de mudanza no puede ser anterior a hoy';
        }

        // Cantidad de ocupantes: entero entre 1 y 50.
        $ocupantes = filter_var(
            $perfil['cantidad_ocupantes'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($ocupantes === false || $ocupantes < 1 || $ocupantes > 50) {
            $errores['cantidad_ocupantes'] = 'Debe ser un entero entre 1 y 50';
        }

        // Tiene mascotas: debe poder interpretarse como booleano.
        $tieneMascotas = filter_var(
            $perfil['tiene_mascotas'] ?? null,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($tieneMascotas === null) {
            $errores['tiene_mascotas'] = 'Debe indicar si tiene mascotas';
        }

        // Cantidad de mascotas: entero entre 0 y 20.
        $cantidadMascotas = filter_var(
            $perfil['cantidad_mascotas'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $cantidadMascotas === false
            || $cantidadMascotas < 0
            || $cantidadMascotas > 20
        ) {
            $errores['cantidad_mascotas'] = 'Debe ser un entero entre 0 y 20';
        } elseif (
            $tieneMascotas !== null
            && (
                ($tieneMascotas && $cantidadMascotas < 1)
                || (!$tieneMascotas && $cantidadMascotas !== 0)
            )
        ) {
            $errores['cantidad_mascotas'] =
                'La cantidad debe coincidir con la información sobre mascotas';
        }

        // Garantías: lista de valores permitidos.
        $garantiasPermitidas = [
            'recibo_sueldo',
            'garantia_propietaria',
            'seguro_caucion',
            'garante',
        ];

        $garantias = $perfil['garantias'] ?? null;

        if (!is_array($garantias)) {
            $errores['garantias'] = 'Debe enviar una lista de garantías';
        } else {
            foreach ($garantias as $garantia) {
                if (
                    !is_string($garantia)
                    || !in_array($garantia, $garantiasPermitidas, true)
                ) {
                    $errores['garantias'] =
                        'La lista contiene una garantía no permitida';
                    break;
                }
            }
        }

        return $errores;
    }
}