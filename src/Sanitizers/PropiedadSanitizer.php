<?php

namespace App\Sanitizers;

class PropiedadSanitizer
{
    public static function sanitizarCrear(array $data): array
    {
        return [
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
            'cantidad_ambientes' => self::sanitizarEnteroPositivo(
                $data['cantidad_ambientes'] ?? null
            ),
            'cantidad_dormitorios' => self::sanitizarEnteroPositivo(
                $data['cantidad_dormitorios'] ?? null
            ),
            'cantidad_banos' => self::sanitizarEnteroPositivo(
                $data['cantidad_banos'] ?? null
            ),
            'capacidad' => self::sanitizarEnteroPositivo(
                $data['capacidad'] ?? null
            ),
            'acepta_mascotas' => self::sanitizarBooleano(
                $data['acepta_mascotas'] ?? null,
                false
            ),
            'acepta_hijos' => self::sanitizarBooleano(
                $data['acepta_hijos'] ?? null,
                false
            ),
            'disponible' => self::sanitizarDisponible(
                $data['disponible'] ?? null
            ),
            'destacada' => self::sanitizarDestacada(
                $data['destacada'] ?? null
            ),
            'categoria_id' => self::sanitizarEnteroPositivo(
                $data['categoria_id'] ?? null
            ),
            'localidad_id' => self::sanitizarEnteroPositivo(
                $data['localidad_id'] ?? null
            ),
        ];
    }

    public static function sanitizarActualizar(array $data): array
    {
        $sanitizado = [];

        if (array_key_exists('titulo', $data)) {
            $sanitizado['titulo'] = self::sanitizarTitulo(
                $data['titulo']
            );
        }

        if (array_key_exists('descripcion', $data)) {
            $sanitizado['descripcion'] = self::sanitizarDescripcion(
                $data['descripcion']
            );
        }

        if (array_key_exists('precio', $data)) {
            $sanitizado['precio'] = self::sanitizarPrecio(
                $data['precio']
            );
        }

        if (array_key_exists('expensas', $data)) {
            $sanitizado['expensas'] = self::sanitizarExpensas(
                $data['expensas']
            );
        }

        if (array_key_exists('direccion', $data)) {
            $sanitizado['direccion'] = self::sanitizarDireccion(
                $data['direccion']
            );
        }

        if (array_key_exists('cantidad_ambientes', $data)) {
            $sanitizado['cantidad_ambientes'] =
                self::sanitizarEnteroPositivo(
                    $data['cantidad_ambientes']
                );
        }

        if (array_key_exists('cantidad_dormitorios', $data)) {
            $sanitizado['cantidad_dormitorios'] =
                self::sanitizarEnteroPositivo(
                    $data['cantidad_dormitorios']
                );
        }

        if (array_key_exists('cantidad_banos', $data)) {
            $sanitizado['cantidad_banos'] =
                self::sanitizarEnteroPositivo(
                    $data['cantidad_banos']
                );
        }

        if (array_key_exists('capacidad', $data)) {
            $sanitizado['capacidad'] =
                self::sanitizarEnteroPositivo(
                    $data['capacidad']
                );
        }

        if (array_key_exists('acepta_mascotas', $data)) {
            $sanitizado['acepta_mascotas'] =
                self::sanitizarBooleano(
                    $data['acepta_mascotas'],
                    false
                );
        }

        if (array_key_exists('acepta_hijos', $data)) {
            $sanitizado['acepta_hijos'] =
                self::sanitizarBooleano(
                    $data['acepta_hijos'],
                    false
                );
        }

        if (array_key_exists('disponible', $data)) {
            $sanitizado['disponible'] =
                self::sanitizarDisponible(
                    $data['disponible']
                );
        }

        if (array_key_exists('destacada', $data)) {
            $sanitizado['destacada'] =
                self::sanitizarDestacada(
                    $data['destacada']
                );
        }

        if (array_key_exists('categoria_id', $data)) {
            $sanitizado['categoria_id'] =
                self::sanitizarEnteroPositivo(
                    $data['categoria_id']
                );
        }

        if (array_key_exists('localidad_id', $data)) {
            $sanitizado['localidad_id'] =
                self::sanitizarEnteroPositivo(
                    $data['localidad_id']
                );
        }

        return $sanitizado;
    }

    /*
    |--------------------------------------------------------------------------
    | FILTROS DE BÚSQUEDA
    |--------------------------------------------------------------------------
    */

    public static function sanitizarFiltros(array $filtros): array
    {
        $sanitizado = [];

        if (array_key_exists('categoria_id', $filtros)) {
            $sanitizado['categoria_id'] =
                self::sanitizarIdsFiltro(
                    $filtros['categoria_id']
                );
        }

        if (array_key_exists('localidad_id', $filtros)) {
            $sanitizado['localidad_id'] =
                self::sanitizarIdsFiltro(
                    $filtros['localidad_id']
                );
        }

        if (array_key_exists('servicio_id', $filtros)) {
            $sanitizado['servicio_id'] =
                self::sanitizarIdsFiltro(
                    $filtros['servicio_id']
                );
        }

        if (array_key_exists('acepta_mascotas', $filtros)) {
            $sanitizado['acepta_mascotas'] =
                self::sanitizarBooleano(
                    $filtros['acepta_mascotas'],
                    false
                );
        }

        if (array_key_exists('acepta_hijos', $filtros)) {
            $sanitizado['acepta_hijos'] =
                self::sanitizarBooleano(
                    $filtros['acepta_hijos'],
                    false
                );
        }

        if (array_key_exists('precio_min', $filtros)) {
            $sanitizado['precio_min'] =
                self::sanitizarPrecioFiltro(
                    $filtros['precio_min']
                );
        }

        if (array_key_exists('precio_max', $filtros)) {
            $sanitizado['precio_max'] =
                self::sanitizarPrecioFiltro(
                    $filtros['precio_max']
                );
        }

        if (array_key_exists('cantidad_ambientes', $filtros)) {
            $sanitizado['cantidad_ambientes'] =
                self::sanitizarCantidadFiltro(
                    $filtros['cantidad_ambientes']
                );
        }

        if (array_key_exists('cantidad_dormitorios', $filtros)) {
            $sanitizado['cantidad_dormitorios'] =
                self::sanitizarCantidadFiltro(
                    $filtros['cantidad_dormitorios']
                );
        }

        if (array_key_exists('cantidad_banos', $filtros)) {
            $sanitizado['cantidad_banos'] =
                self::sanitizarCantidadFiltro(
                    $filtros['cantidad_banos']
                );
        }

        if (array_key_exists('capacidad', $filtros)) {
            $sanitizado['capacidad'] =
                self::sanitizarCantidadFiltro(
                    $filtros['capacidad']
                );
        }

        return $sanitizado;
    }

    public static function sanitizarPrecioFiltro($precio): ?string
    {
        if ($precio === null || $precio === '') {
            return null;
        }

        return str_replace(
            ',',
            '.',
            trim((string) $precio)
        );
    }

    public static function sanitizarCantidadFiltro(
        $cantidad
    ): ?string {
        if ($cantidad === null || $cantidad === '') {
            return null;
        }

        return trim((string) $cantidad);
    }

    public static function sanitizarIdsFiltro($ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $resultado = [];

        foreach ($ids as $id) {
            if ($id === null) {
                $resultado[] = null;
                continue;
            }

            $id = trim((string) $id);

            if ($id === '') {
                $resultado[] = '';
                continue;
            }

            if (
                ctype_digit($id) &&
                (int) $id > 0
            ) {
                $resultado[] = (int) $id;
            } else {
                $resultado[] = $id;
            }
        }

        return array_values(
            array_unique(
                $resultado,
                SORT_REGULAR
            )
        );
    }

    public static function sanitizarId($id): ?int
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

    public static function sanitizarTitulo($titulo): ?string
    {
        if (!is_string($titulo)) {
            return null;
        }

        $titulo = trim($titulo);

        $titulo = preg_replace(
            '/\s+/u',
            ' ',
            $titulo
        );

        return mb_substr(
            $titulo,
            0,
            150
        );
    }

    public static function sanitizarDescripcion(
        $descripcion
    ): ?string {
        if (!is_string($descripcion)) {
            return null;
        }

        $descripcion = trim($descripcion);

        $descripcion = preg_replace(
            '/\s+/u',
            ' ',
            $descripcion
        );

        return $descripcion;
    }

    public static function sanitizarPrecio($precio): ?float
    {
        if ($precio === null || $precio === '') {
            return null;
        }

        $precio = str_replace(
            ',',
            '.',
            (string) $precio
        );

        if (!is_numeric($precio)) {
            return null;
        }

        return round(
            (float) $precio,
            2
        );
    }

    public static function sanitizarExpensas(
        $expensas
    ): ?float {
        if ($expensas === null || $expensas === '') {
            return 0;
        }

        $expensas = str_replace(
            ',',
            '.',
            (string) $expensas
        );

        if (!is_numeric($expensas)) {
            return null;
        }

        return round(
            (float) $expensas,
            2
        );
    }

    public static function sanitizarDireccion(
        $direccion
    ): ?string {
        if (!is_string($direccion)) {
            return null;
        }

        $direccion = trim($direccion);

        $direccion = preg_replace(
            '/\s+/u',
            ' ',
            $direccion
        );

        return mb_substr(
            $direccion,
            0,
            125
        );
    }

    public static function sanitizarEnteroPositivo(
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

    public static function sanitizarDisponible(
        $disponible
    ): ?int {
        if ($disponible === null || $disponible === '') {
            return 1;
        }

        $valor = filter_var(
            $disponible,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        return $valor === null
            ? null
            : ($valor ? 1 : 0);
    }

    public static function sanitizarBooleano(
        $valor,
        bool $default = null
    ): ?int {
        if ($valor === null || $valor === '') {
            return $default === null
                ? null
                : ($default ? 1 : 0);
        }

        $resultado = filter_var(
            $valor,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        return $resultado === null
            ? null
            : ($resultado ? 1 : 0);
    }

    public static function sanitizarDestacada(
        $destacada
    ): ?int {
        if ($destacada === null || $destacada === '') {
            return 0;
        }

        $valor = filter_var(
            $destacada,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        return $valor === null
            ? null
            : ($valor ? 1 : 0);
    }
}