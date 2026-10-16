<?php

namespace App\Validators;

class PropiedadValidator
{
    public static function validarId($id): ?string
    {
        if ($id === null || $id === '') {
            return 'El ID de propiedad es requerido';
        }

        if (!is_numeric($id)) {
            return 'El ID de propiedad debe ser numérico';
        }

        if ((int) $id <= 0) {
            return 'El ID de propiedad debe ser positivo';
        }

        if (filter_var($id, FILTER_VALIDATE_INT) === false) {
            return 'El ID de propiedad debe ser un número entero';
        }

        return null;
    }

    public static function validarTitulo(
        ?string $titulo
    ): ?string {
        if ($titulo === null || $titulo === '') {
            return 'El título es obligatorio';
        }

        $titulo = trim($titulo);

        if (mb_strlen($titulo) < 3) {
            return 'El título debe tener al menos 3 caracteres';
        }

        if (mb_strlen($titulo) > 150) {
            return 'El título no puede superar los 150 caracteres';
        }

        return null;
    }

    public static function validarDescripcion(
        ?string $descripcion
    ): ?string {
        if ($descripcion === null) {
            return null;
        }

        if (mb_strlen($descripcion) > 5000) {
            return 'La descripción no puede superar los 5000 caracteres';
        }

        return null;
    }

    public static function validarPrecio(
        $precio
    ): ?string {
        if ($precio === null) {
            return 'El precio es obligatorio';
        }

        if (!is_numeric($precio)) {
            return 'El precio debe ser numérico';
        }

        if ($precio <= 0) {
            return 'El precio debe ser mayor a 0';
        }

        return null;
    }

    public static function validarExpensas(
        $expensas
    ): ?string {
        if ($expensas === null) {
            return 'Las expensas son obligatorias';
        }

        if (!is_numeric($expensas)) {
            return 'Las expensas son inválidas';
        }

        if ($expensas < 0) {
            return 'Las expensas no pueden ser negativas';
        }

        return null;
    }

    public static function validarDireccion(
        ?string $direccion
    ): ?string {
        if ($direccion === null || $direccion === '') {
            return 'La dirección es obligatoria';
        }

        $direccion = trim($direccion);

        if (mb_strlen($direccion) < 3) {
            return 'La dirección debe tener al menos 3 caracteres';
        }

        if (mb_strlen($direccion) > 125) {
            return 'La dirección no puede superar los 125 caracteres';
        }

        return null;
    }

    public static function validarCantidadAmbientes(
        $cantidad
    ): ?string {
        if ($cantidad === null) {
            return 'La cantidad de ambientes es obligatoria';
        }

        if (!is_int($cantidad) || $cantidad < 1) {
            return 'La cantidad de ambientes debe ser mayor a 0';
        }

        return null;
    }

    public static function validarCantidadDormitorios(
        $cantidad,
        $cantidadAmbientes
    ): ?string {
        if ($cantidad === null) {
            return 'La cantidad de dormitorios es obligatoria';
        }

        if (!is_int($cantidad) || $cantidad < 1) {
            return 'La cantidad de dormitorios debe ser mayor a 0';
        }

        if (
            is_int($cantidadAmbientes)
            && $cantidad > $cantidadAmbientes
        ) {
            return 'Los dormitorios no pueden superar la cantidad de ambientes';
        }

        return null;
    }

    public static function validarCantidadBanos(
        $cantidad,
        $cantidadAmbientes
    ): ?string {
        if ($cantidad === null) {
            return 'La cantidad de baños es obligatoria';
        }

        if (!is_int($cantidad) || $cantidad < 1) {
            return 'La cantidad de baños debe ser mayor a 0';
        }

        if (
            is_int($cantidadAmbientes)
            && $cantidad > $cantidadAmbientes
        ) {
            return 'Los baños no pueden superar la cantidad de ambientes';
        }

        return null;
    }

    public static function validarCapacidad(
        $capacidad
    ): ?string {
        if ($capacidad === null) {
            return null;
        }

        if (!is_int($capacidad) || $capacidad < 1) {
            return 'La capacidad debe ser mayor a 0';
        }

        return null;
    }

    public static function validarDisponible(
        $disponible
    ): ?string {
        if (!in_array($disponible, [0, 1], true)) {
            return 'El estado de disponibilidad es inválido';
        }

        return null;
    }

    public static function validarBooleano(
        $valor,
        string $campo
    ): ?string {
        if ($valor === null) {
            return null;
        }

        if (!in_array($valor, [0, 1], true)) {
            return "El campo {$campo} es inválido";
        }

        return null;
    }

    public static function validarRequisitosInteresados($requisitos): array
    {
        if ($requisitos === null) {
            return [];
        }

        if (!is_array($requisitos)) {
            return [
                'Los requisitos deben enviarse como un objeto',
            ];
        }

        $errores = [];

        // Validar fecha de disponibilidad.
        $fecha = $requisitos['fecha_disponible_desde'] ?? null;

        if ($fecha !== null && $fecha !== '') {
            $date = is_string($fecha)
                ? \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha)
                : false;

            if (
                !$date ||
                $date->format('Y-m-d') !== $fecha
            ) {
                $errores[] =
                    'La fecha de disponibilidad debe tener formato AAAA-MM-DD';
            }
        }

        // Validar cantidad máxima de ocupantes.
        $maxOcupantes = $requisitos['max_ocupantes'] ?? null;

        if ($maxOcupantes !== null && $maxOcupantes !== '') {
            if (
                filter_var(
                    $maxOcupantes,
                    FILTER_VALIDATE_INT
                ) === false ||
                (int) $maxOcupantes < 1 ||
                (int) $maxOcupantes > 50
            ) {
                $errores[] =
                    'El máximo de ocupantes debe ser un entero entre 1 y 50';
            }
        }

        // Validar garantías aceptadas.
        $garantias = $requisitos['garantias_aceptadas'] ?? [];

        $garantiasPermitidas = [
            'recibo_sueldo',
            'garantia_propietaria',
            'seguro_caucion',
            'garante',
        ];

        if (!is_array($garantias) || !array_is_list($garantias)) {
            $errores[] =
                'Las garantías aceptadas deben ser una lista';
        } else {
            foreach ($garantias as $garantia) {
                if (
                    !is_string($garantia) ||
                    !in_array($garantia, $garantiasPermitidas, true)
                ) {
                    $errores[] =
                        'La lista contiene una garantía no válida';
                    break;
                }
            }
        }

        return $errores;
    }

    public static function validarDestacada(
        $destacada
    ): ?string {
        if ($destacada === null) {
            return 'El campo destacada es inválido';
        }

        if (!in_array($destacada, [0, 1], true)) {
            return 'El campo destacada es inválido';
        }

        return null;
    }

    public static function validarCategoriaId(
        $categoriaId
    ): ?string {
        if ($categoriaId === null) {
            return 'La categoría es obligatoria';
        }

        if (!is_int($categoriaId) || $categoriaId <= 0) {
            return 'La categoría debe ser un ID válido';
        }

        return null;
    }

    public static function validarLocalidadId(
        $localidadId
    ): ?string {
        if ($localidadId === null) {
            return 'La localidad es obligatoria';
        }

        if (!is_int($localidadId) || $localidadId <= 0) {
            return 'La localidad debe ser un ID válido';
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDACIONES DE FILTROS DE BÚSQUEDA
    |--------------------------------------------------------------------------
    */

    public static function validarPrecioFiltro(
        $precio
    ): ?string {
        if ($precio === null) {
            return null;
        }

        if (!is_numeric($precio)) {
            return 'El precio debe ser numérico';
        }

        if ((float) $precio < 0) {
            return 'El precio no puede ser negativo';
        }

        return null;
    }

    public static function validarCantidadFiltro(
        $cantidad
    ): ?string {
        if ($cantidad === null) {
            return null;
        }

        if (
            !is_numeric($cantidad) ||
            filter_var(
                $cantidad,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            return 'La cantidad debe ser un entero mayor a 0';
        }

        if ((int) $cantidad < 1) {
            return 'La cantidad debe ser un entero mayor a 0';
        }

        return null;
    }

    public static function validarRangoPrecio(
        $precioMin,
        $precioMax
    ): ?string {
        if (
            $precioMin === null ||
            $precioMax === null
        ) {
            return null;
        }

        if (
            (float) $precioMin >
            (float) $precioMax
        ) {
            return 'El precio mínimo no puede ser mayor que el precio máximo';
        }

        return null;
    }

    public static function validarIdsFiltro(
        $ids,
        string $nombreCampo
    ): ?string {
        if ($ids === null || $ids === []) {
            return null;
        }

        if (!is_array($ids)) {
            return "El campo {$nombreCampo} debe ser un arreglo";
        }

        foreach ($ids as $id) {
            if (
                filter_var(
                    $id,
                    FILTER_VALIDATE_INT
                ) === false ||
                (int) $id <= 0
            ) {
                return "El campo {$nombreCampo} contiene un ID inválido";
            }
        }

        return null;
    }

    public static function validarFiltros(
        array $data
    ): array {
        $errores = [];

        $validaciones = [
            'categoria_id' => self::validarIdsFiltro(
                $data['categoria_id'] ?? null,
                'categoria_id'
            ),

            'localidad_id' => self::validarIdsFiltro(
                $data['localidad_id'] ?? null,
                'localidad_id'
            ),

            'servicio_id' => self::validarIdsFiltro(
                $data['servicio_id'] ?? null,
                'servicio_id'
            ),

            'acepta_mascotas' => self::validarBooleano(
                $data['acepta_mascotas'] ?? null,
                'acepta_mascotas'
            ),

            'acepta_hijos' => self::validarBooleano(
                $data['acepta_hijos'] ?? null,
                'acepta_hijos'
            ),

            'precio_min' => self::validarPrecioFiltro(
                $data['precio_min'] ?? null
            ),

            'precio_max' => self::validarPrecioFiltro(
                $data['precio_max'] ?? null
            ),

            'cantidad_ambientes' =>
                self::validarCantidadFiltro(
                    $data['cantidad_ambientes'] ?? null
                ),

            'cantidad_dormitorios' =>
                self::validarCantidadFiltro(
                    $data['cantidad_dormitorios'] ?? null
                ),

            'cantidad_banos' =>
                self::validarCantidadFiltro(
                    $data['cantidad_banos'] ?? null
                ),

            'capacidad' => self::validarCantidadFiltro(
                $data['capacidad'] ?? null
            ),

            'rango_precio' => self::validarRangoPrecio(
                $data['precio_min'] ?? null,
                $data['precio_max'] ?? null
            ),
        ];

        foreach ($validaciones as $campo => $error) {
            if ($error !== null) {
                $errores[$campo] = $error;
            }
        }

        return [
            'success' => empty($errores),
            'message' => empty($errores)
                ? 'Validación exitosa'
                : 'Error de validación',
            'errors' => empty($errores)
                ? null
                : $errores,
        ];
    }

    public static function validar(array $data): array
    {
        $errores = [];

        $validaciones = [
            'titulo' => self::validarTitulo(
                $data['titulo'] ?? null
            ),

            'descripcion' => self::validarDescripcion(
                $data['descripcion'] ?? null
            ),

            'precio' => self::validarPrecio(
                $data['precio'] ?? null
            ),

            'expensas' => self::validarExpensas(
                $data['expensas'] ?? null
            ),

            'direccion' => self::validarDireccion(
                $data['direccion'] ?? null
            ),

            'cantidad_ambientes' =>
                self::validarCantidadAmbientes(
                    $data['cantidad_ambientes'] ?? null
                ),

            'cantidad_dormitorios' =>
                self::validarCantidadDormitorios(
                    $data['cantidad_dormitorios'] ?? null,
                    $data['cantidad_ambientes'] ?? null
                ),

            'cantidad_banos' =>
                self::validarCantidadBanos(
                    $data['cantidad_banos'] ?? null,
                    $data['cantidad_ambientes'] ?? null
                ),

            'capacidad' => self::validarCapacidad(
                $data['capacidad'] ?? null
            ),

            'acepta_mascotas' => self::validarBooleano(
                $data['acepta_mascotas'] ?? null,
                'acepta_mascotas'
            ),

            'acepta_hijos' => self::validarBooleano(
                $data['acepta_hijos'] ?? null,
                'acepta_hijos'
            ),

            'disponible' => self::validarDisponible(
                $data['disponible'] ?? null
            ),

            'destacada' => self::validarDestacada(
                $data['destacada'] ?? null
            ),

            'categoria_id' => self::validarCategoriaId(
                $data['categoria_id'] ?? null
            ),

            'localidad_id' => self::validarLocalidadId(
                $data['localidad_id'] ?? null
            ),
        ];

       foreach ($validaciones as $campo => $error) {
            if ($error === null) {
                continue;
            }

            if (is_array($error)) {
                if ($error !== []) {
                    $errores[$campo] = $error;
                }

                continue;
            }

            $errores[$campo] = $error;
        }

        return [
            'success' => empty($errores),
            'message' => empty($errores)
                ? 'Validación exitosa'
                : 'Error de validación',
            'errors' => empty($errores)
                ? null
                : $errores,
        ];
    }

    public static function validarSoloId($id): array
    {
        $error = self::validarId($id);

        if ($error) {
            return [
                'success' => false,
                'message' => 'ID inválido',
                'errors' => [
                    'id' => $error,
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