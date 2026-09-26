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
                : $errores
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
                    'id' => $error
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