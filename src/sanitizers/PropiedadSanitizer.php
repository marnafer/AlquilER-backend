<?php

namespace App\Sanitizers;

class PropiedadSanitizer
{
    /**
     * Sanitizar propiedad completa
     */
    public static function sanitizarPropiedad(
        array $data
    ): array {

        return [
            'id' => self::sanitizarIdPropiedad(
                $data['id'] ?? null
            ),
            'titulo' => self::sanitizarTitulo(
                $data['titulo'] ?? null
            ),
            'descripcion' => self::sanitizarDescripcion(
                $data['descripcion'] ?? null
            ),
            'precio' => self::sanitizarPrecio(
                $data['precio'] ?? null
            ),
            'expensas' => self::sanitizarExpensas(
                $data['expensas'] ?? null
            ),
            'direccion' => self::sanitizarDireccion(
                $data['direccion'] ?? null
            ),
            'cantidad_ambientes' => self::sanitizarEntero(
                $data['cantidad_ambientes'] ?? null
            ),
            'cantidad_dormitorios' => self::sanitizarEntero(
                $data['cantidad_dormitorios'] ?? null
            ),
            'cantidad_banos' => self::sanitizarEntero(
                $data['cantidad_banos'] ?? null
            ),
            'capacidad' => self::sanitizarEntero(
                $data['capacidad'] ?? null
            ),
            'disponible' => self::sanitizarDisponible(
                $data['disponible'] ?? null
            ),
            'categoria_id' => self::sanitizarEntero(
                $data['categoria_id'] ?? null
            ),
            'localidad_id' => self::sanitizarEntero(
                $data['localidad_id'] ?? null
            ),
            'usuario_id' => self::sanitizarEntero(
                $data['usuario_id'] ?? null
            )
        ];
    }

    /**
     * Sanitizar ID
     */
    public static function sanitizarIdPropiedad($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var(
            $id,
            FILTER_VALIDATE_INT
        );

        return (
            $id !== false &&
            $id > 0
        )
            ? $id
            : null;
    }

    /**
     * Sanitizar título
     */
    public static function sanitizarTitulo(
        $titulo
    ): ?string {

        if ($titulo === null || $titulo === '') {
            return null;
        }

        $titulo = trim($titulo);
        $titulo = preg_replace('/\s+/', ' ', $titulo);
        $titulo = strip_tags($titulo);
        $titulo = htmlspecialchars(
            $titulo,
            ENT_QUOTES,
            'UTF-8'
        );

        return $titulo;
    }

    /**
     * Sanitizar descripción
     */
    public static function sanitizarDescripcion(
        $descripcion
    ): ?string {

        if ($descripcion === null || $descripcion === '') {
            return null;
        }

        $descripcion = trim($descripcion);
        $descripcion = preg_replace('/\s+/', ' ', $descripcion);
        $descripcion = strip_tags($descripcion);
        $descripcion = htmlspecialchars(
            $descripcion,
            ENT_QUOTES,
            'UTF-8'
        );

        return $descripcion;
    }

    /**
     * Sanitizar precio
     */
    public static function sanitizarPrecio(
        $precio
    ): ?float {

        if ($precio === null || $precio === '') {
            return null;
        }

        $precio = str_replace(',', '.', $precio);

        $precio = filter_var(
            $precio,
            FILTER_VALIDATE_FLOAT
        );

        return $precio !== false
            ? round($precio, 2)
            : null;
    }

    /**
     * Sanitizar expensas
     */
    public static function sanitizarExpensas(
        $expensas
    ): ?float {

        if ($expensas === null || $expensas === '') {
            return 0;
        }

        $expensas = str_replace(',', '.', $expensas);

        $expensas = filter_var(
            $expensas,
            FILTER_VALIDATE_FLOAT
        );

        return $expensas !== false
            ? round($expensas, 2)
            : 0;
    }

    /**
     * Sanitizar dirección
     */
    public static function sanitizarDireccion(
        $direccion
    ): ?string {

        if ($direccion === null || $direccion === '') {
            return null;
        }

        $direccion = trim($direccion);
        $direccion = preg_replace('/\s+/', ' ', $direccion);
        $direccion = strip_tags($direccion);
        $direccion = htmlspecialchars(
            $direccion,
            ENT_QUOTES,
            'UTF-8'
        );

        return $direccion;
    }

    /**
     * Sanitizar entero positivo
     */
    public static function sanitizarEntero(
        $valor
    ): ?int {

        if ($valor === null || $valor === '') {
            return null;
        }

        $valorSanitizado = filter_var(
            $valor,
            FILTER_VALIDATE_INT
        );

        return (
            $valorSanitizado !== false &&
            $valorSanitizado > 0
        )
            ? $valorSanitizado
            : null;
    }

    /**
     * Sanitizar disponible
     */
    public static function sanitizarDisponible(
        $disponible
    ): int {

        if ($disponible === null || $disponible === '') {
            return 1;
        }

        return filter_var(
            $disponible,
            FILTER_VALIDATE_BOOLEAN
        )
            ? 1
            : 0;
    }
}