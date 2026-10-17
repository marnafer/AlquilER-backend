<?php

declare(strict_types=1);

namespace Tests\Unit\Validators;

use App\Validators\PropiedadValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PropiedadValidatorTest extends TestCase
{
    #[DataProvider('idsInvalidos')]
    public function test_validar_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            PropiedadValidator::validarId($id)
        );
    }

    public static function idsInvalidos(): array
    {
        return [
            'null' => [null, 'El ID de propiedad es requerido'],
            'vacio' => ['', 'El ID de propiedad es requerido'],
            'texto' => ['abc', 'El ID de propiedad debe ser numérico'],
            'cero' => [0, 'El ID de propiedad debe ser positivo'],
            'negativo' => [-2, 'El ID de propiedad debe ser positivo'],
            'decimal' => [
                1.5,
                'El ID de propiedad debe ser un número entero',
            ],
            'decimal_como_texto' => [
                '1.5',
                'El ID de propiedad debe ser un número entero',
            ],
        ];
    }

    #[DataProvider('idsValidos')]
    public function test_validar_id_ok(mixed $id): void
    {
        $this->assertNull(PropiedadValidator::validarId($id));
    }

    public static function idsValidos(): array
    {
        return [
            'entero' => [1],
            'cadena_numerica' => ['1'],
        ];
    }

    #[DataProvider('titulosInvalidos')]
    public function test_validar_titulo_falla(
        ?string $titulo,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            PropiedadValidator::validarTitulo($titulo)
        );
    }

    public static function titulosInvalidos(): array
    {
        return [
            'null' => [null, 'El título es obligatorio'],
            'vacio' => ['', 'El título es obligatorio'],
            'corto' => [
                'ab',
                'El título debe tener al menos 3 caracteres',
            ],
            'espacios_solamente' => [
                '   ',
                'El título debe tener al menos 3 caracteres',
            ],
            'largo' => [
                str_repeat('a', 151),
                'El título no puede superar los 150 caracteres',
            ],
        ];
    }

    #[DataProvider('titulosValidos')]
    public function test_validar_titulo_ok(string $titulo): void
    {
        $this->assertNull(PropiedadValidator::validarTitulo($titulo));
    }

    public static function titulosValidos(): array
    {
        return [
            'simple' => ['Depto'],
            'con_espacios_al_costado' => ['  Depto luminoso  '],
            'maximo' => [str_repeat('a', 150)],
        ];
    }

    public function test_validar_descripcion_null_es_ok(): void
    {
        $this->assertNull(PropiedadValidator::validarDescripcion(null));
    }

    public function test_validar_descripcion_corta_es_ok(): void
    {
        $this->assertNull(
            PropiedadValidator::validarDescripcion('Muy luminoso')
        );
    }

    public function test_validar_descripcion_larga_falla(): void
    {
        $this->assertSame(
            'La descripción no puede superar los 5000 caracteres',
            PropiedadValidator::validarDescripcion(
                str_repeat('a', 5001)
            )
        );
    }

    #[DataProvider('preciosInvalidos')]
    public function test_validar_precio_falla(
        mixed $precio,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            PropiedadValidator::validarPrecio($precio)
        );
    }

    public static function preciosInvalidos(): array
    {
        return [
            'null' => [null, 'El precio es obligatorio'],
            'texto' => ['gratis', 'El precio debe ser numérico'],
            'cero' => [0, 'El precio debe ser mayor a 0'],
            'negativo' => [-100, 'El precio debe ser mayor a 0'],
        ];
    }

    #[DataProvider('preciosValidos')]
    public function test_validar_precio_ok(mixed $precio): void
    {
        $this->assertNull(PropiedadValidator::validarPrecio($precio));
    }

    public static function preciosValidos(): array
    {
        return [
            'entero' => [50000],
            'decimal' => [50000.5],
            'cadena_numerica' => ['50000'],
        ];
    }

    #[DataProvider('expensasInvalidas')]
    public function test_validar_expensas_falla(
        mixed $expensas,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            PropiedadValidator::validarExpensas($expensas)
        );
    }

    public static function expensasInvalidas(): array
    {
        return [
            'null' => [null, 'Las expensas son obligatorias'],
            'texto' => ['nada', 'Las expensas son inválidas'],
            'negativo' => [
                -1,
                'Las expensas no pueden ser negativas',
            ],
        ];
    }

    #[DataProvider('expensasValidas')]
    public function test_validar_expensas_ok(mixed $expensas): void
    {
        $this->assertNull(PropiedadValidator::validarExpensas($expensas));
    }

    public static function expensasValidas(): array
    {
        return [
            'cero' => [0],
            'entero' => [15000],
            'cadena_numerica' => ['0'],
        ];
    }

    #[DataProvider('direccionesInvalidas')]
    public function test_validar_direccion_falla(
        ?string $direccion,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            PropiedadValidator::validarDireccion($direccion)
        );
    }

    public static function direccionesInvalidas(): array
    {
        return [
            'null' => [null, 'La dirección es obligatoria'],
            'vacia' => ['', 'La dirección es obligatoria'],
            'corta' => [
                'av',
                'La dirección debe tener al menos 3 caracteres',
            ],
            'espacios_solamente' => [
                '   ',
                'La dirección debe tener al menos 3 caracteres',
            ],
            'larga' => [
                str_repeat('a', 126),
                'La dirección no puede superar los 125 caracteres',
            ],
        ];
    }

    #[DataProvider('direccionesValidas')]
    public function test_validar_direccion_ok(string $direccion): void
    {
        $this->assertNull(PropiedadValidator::validarDireccion($direccion));
    }

    public static function direccionesValidas(): array
    {
        return [
            'simple' => ['Av. Siempreviva 742'],
            'con_espacios_al_costado' => ['  Av. Siempreviva 742  '],
            'maxima' => [str_repeat('a', 125)],
        ];
    }

    #[DataProvider('cantidadesInvalidas')]
    public function test_validar_cantidad_ambientes_falla(
        mixed $cantidad,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            PropiedadValidator::validarCantidadAmbientes($cantidad)
        );
    }

    public static function cantidadesInvalidas(): array
    {
        return [
            'null' => [
                null,
                'La cantidad de ambientes es obligatoria',
            ],
            'cero' => [
                0,
                'La cantidad de ambientes debe ser mayor a 0',
            ],
            'negativo' => [
                -1,
                'La cantidad de ambientes debe ser mayor a 0',
            ],
            'cadena_numerica' => [
                '2',
                'La cantidad de ambientes debe ser mayor a 0',
            ],
            'decimal' => [
                1.5,
                'La cantidad de ambientes debe ser mayor a 0',
            ],
        ];
    }

    #[DataProvider('cantidadesValidas')]
    public function test_validar_cantidad_ambientes_ok(int $cantidad): void
    {
        $this->assertNull(
            PropiedadValidator::validarCantidadAmbientes($cantidad)
        );
    }

    public static function cantidadesValidas(): array
    {
        return [
            'minima' => [1],
            'varios' => [4],
        ];
    }

    public function test_validar_cantidad_dormitorios_null_falla(): void
    {
        $this->assertSame(
            'La cantidad de dormitorios es obligatoria',
            PropiedadValidator::validarCantidadDormitorios(null, 2)
        );
    }

    #[DataProvider('cantidadesInvalidas')]
    public function test_validar_cantidad_dormitorios_invalida_falla(
        mixed $cantidad,
        string $errorEsperado
    ): void {
        $esperado = str_replace(
            'ambientes',
            'dormitorios',
            $errorEsperado
        );

        $this->assertSame(
            $esperado,
            PropiedadValidator::validarCantidadDormitorios($cantidad, 3)
        );
    }

    public function test_validar_cantidad_dormitorios_supera_ambientes(): void
    {
        $this->assertSame(
            'Los dormitorios no pueden superar la cantidad de ambientes',
            PropiedadValidator::validarCantidadDormitorios(3, 2)
        );
    }

    public function test_validar_cantidad_dormitorios_ok(): void
    {
        $this->assertNull(
            PropiedadValidator::validarCantidadDormitorios(2, 2)
        );
    }

    public function test_validar_cantidad_dormitorios_sin_ambientes_validos(): void
    {
        $this->assertNull(
            PropiedadValidator::validarCantidadDormitorios(2, 'x')
        );
    }

    public function test_validar_cantidad_banos_null_falla(): void
    {
        $this->assertSame(
            'La cantidad de baños es obligatoria',
            PropiedadValidator::validarCantidadBanos(null, 2)
        );
    }

    #[DataProvider('cantidadesInvalidas')]
    public function test_validar_cantidad_banos_invalida_falla(
        mixed $cantidad,
        string $errorEsperado
    ): void {
        $esperado = str_replace('ambientes', 'baños', $errorEsperado);

        $this->assertSame(
            $esperado,
            PropiedadValidator::validarCantidadBanos($cantidad, 3)
        );
    }

    public function test_validar_cantidad_banos_supera_ambientes(): void
    {
        $this->assertSame(
            'Los baños no pueden superar la cantidad de ambientes',
            PropiedadValidator::validarCantidadBanos(5, 2)
        );
    }

    public function test_validar_cantidad_banos_ok(): void
    {
        $this->assertNull(
            PropiedadValidator::validarCantidadBanos(1, 2)
        );
    }

    public function test_validar_cantidad_banos_sin_ambientes_validos(): void
    {
        $this->assertNull(
            PropiedadValidator::validarCantidadBanos(2, null)
        );
    }

    public function test_validar_capacidad_null_es_ok(): void
    {
        $this->assertNull(PropiedadValidator::validarCapacidad(null));
    }

    #[DataProvider('capacidadesInvalidas')]
    public function test_validar_capacidad_invalida_falla(
        mixed $capacidad
    ): void {
        $this->assertSame(
            'La capacidad debe ser mayor a 0',
            PropiedadValidator::validarCapacidad($capacidad)
        );
    }

    public static function capacidadesInvalidas(): array
    {
        return [
            'cero' => [0],
            'negativo' => [-1],
            'cadena_numerica' => ['2'],
            'decimal' => [1.5],
        ];
    }

    #[DataProvider('cantidadesValidas')]
    public function test_validar_capacidad_ok(int $capacidad): void
    {
        $this->assertNull(
            PropiedadValidator::validarCapacidad($capacidad)
        );
    }

    #[DataProvider('disponiblesInvalidos')]
    public function test_validar_disponible_falla(mixed $disponible): void
    {
        $this->assertSame(
            'El estado de disponibilidad es inválido',
            PropiedadValidator::validarDisponible($disponible)
        );
    }

    public static function disponiblesInvalidos(): array
    {
        return [
            'null' => [null],
            'uno_como_texto' => ['1'],
            'booleano_true' => [true],
            'dos' => [2],
        ];
    }

    #[DataProvider('disponiblesValidos')]
    public function test_validar_disponible_ok(int $disponible): void
    {
        $this->assertNull(
            PropiedadValidator::validarDisponible($disponible)
        );
    }

    public static function disponiblesValidos(): array
    {
        return [
            'cero' => [0],
            'uno' => [1],
        ];
    }

    public function test_validar_booleano_null_es_ok(): void
    {
        $this->assertNull(
            PropiedadValidator::validarBooleano(null, 'acepta_mascotas')
        );
    }

    #[DataProvider('disponiblesValidos')]
    public function test_validar_booleano_ok(int $valor): void
    {
        $this->assertNull(
            PropiedadValidator::validarBooleano($valor, 'acepta_mascotas')
        );
    }

    #[DataProvider('valoresBooleanosInvalidos')]
    public function test_validar_booleano_falla(
        mixed $valor
    ): void {
        $this->assertSame(
            'El campo acepta_mascotas es inválido',
            PropiedadValidator::validarBooleano($valor, 'acepta_mascotas')
        );
    }

    public static function valoresBooleanosInvalidos(): array
    {
        return [
            'uno_como_texto' => ['1'],
            'booleano_true' => [true],
            'dos' => [2],
        ];
    }

    public function test_validar_requisitos_interesados_null_es_vacio(): void
    {
        $this->assertSame(
            [],
            PropiedadValidator::validarRequisitosInteresados(null)
        );
    }

    public function test_validar_requisitos_interesados_no_array_falla(): void
    {
        $this->assertSame(
            ['Los requisitos deben enviarse como un objeto'],
            PropiedadValidator::validarRequisitosInteresados('mal')
        );
    }

    public function test_validar_requisitos_interesados_vacios_es_ok(): void
    {
        $this->assertSame(
            [],
            PropiedadValidator::validarRequisitosInteresados([])
        );
    }

    public function test_validar_requisitos_interesados_validos_es_ok(): void
    {
        $this->assertSame(
            [],
            PropiedadValidator::validarRequisitosInteresados([
                'fecha_disponible_desde' => '2026-12-01',
                'max_ocupantes' => 4,
                'garantias_aceptadas' => ['garante', 'seguro_caucion'],
            ])
        );
    }

    #[DataProvider('fechasDeDisponibilidadInvalidas')]
    public function test_validar_requisitos_con_fecha_invalida_falla(
        mixed $fecha
    ): void {
        $errores = PropiedadValidator::validarRequisitosInteresados([
            'fecha_disponible_desde' => $fecha,
        ]);

        $this->assertSame(
            [
                'La fecha de disponibilidad debe tener formato AAAA-MM-DD',
            ],
            $errores
        );
    }

    public static function fechasDeDisponibilidadInvalidas(): array
    {
        return [
            'texto' => ['mañana'],
            'sin_formato' => ['01/12/2026'],
            'dia_inexistente' => ['2026-02-31'],
            'no_string' => [123],
        ];
    }

    #[DataProvider('valoresQueSeOmitean')]
    public function test_validar_requisitos_omite_fecha_vacia(
        mixed $fecha
    ): void {
        $errores = PropiedadValidator::validarRequisitosInteresados([
            'fecha_disponible_desde' => $fecha,
        ]);

        $this->assertSame([], $errores);
    }

    public static function valoresQueSeOmitean(): array
    {
        return [
            'null' => [null],
            'vacio' => [''],
        ];
    }

    #[DataProvider('ocupantesInvalidos')]
    public function test_validar_requisitos_con_ocupantes_invalidos_falla(
        mixed $ocupantes
    ): void {
        $errores = PropiedadValidator::validarRequisitosInteresados([
            'max_ocupantes' => $ocupantes,
        ]);

        $this->assertSame(
            ['El máximo de ocupantes debe ser un entero entre 1 y 50'],
            $errores
        );
    }

    public static function ocupantesInvalidos(): array
    {
        return [
            'cero' => [0],
            'negativo' => [-1],
            'cincuenta_y_uno' => [51],
            'texto' => ['muchos'],
            'decimal' => [2.5],
        ];
    }

    #[DataProvider('ocupantesValidos')]
    public function test_validar_requisitos_con_ocupantes_validos_es_ok(
        mixed $ocupantes
    ): void {
        $errores = PropiedadValidator::validarRequisitosInteresados([
            'max_ocupantes' => $ocupantes,
        ]);

        $this->assertSame([], $errores);
    }

    public static function ocupantesValidos(): array
    {
        return [
            'minimo' => [1],
            'maximo' => [50],
            'cadena_numerica' => ['3'],
        ];
    }

    public function test_validar_requisitos_con_garantias_no_lista_falla(): void
    {
        $errores = PropiedadValidator::validarRequisitosInteresados([
            'garantias_aceptadas' => [
                'garante' => 'seguro_caucion',
            ],
        ]);

        $this->assertSame(
            ['Las garantías aceptadas deben ser una lista'],
            $errores
        );
    }

    public function test_validar_requisitos_con_garantia_no_string_falla(): void
    {
        $errores = PropiedadValidator::validarRequisitosInteresados([
            'garantias_aceptadas' => ['garante', 7],
        ]);

        $this->assertSame(
            ['La lista contiene una garantía no válida'],
            $errores
        );
    }

    public function test_validar_requisitos_con_garantia_desconocida_falla(): void
    {
        $errores = PropiedadValidator::validarRequisitosInteresados([
            'garantias_aceptadas' => ['recibo_sueldo', 'cripto'],
        ]);

        $this->assertSame(
            ['La lista contiene una garantía no válida'],
            $errores
        );
    }

    public function test_validar_requisitos_con_garantias_vacias_es_ok(): void
    {
        $errores = PropiedadValidator::validarRequisitosInteresados([
            'garantias_aceptadas' => [],
        ]);

        $this->assertSame([], $errores);
    }

    public function test_validar_destacada_null_falla(): void
    {
        $this->assertSame(
            'El campo destacada es inválido',
            PropiedadValidator::validarDestacada(null)
        );
    }

    #[DataProvider('disponiblesInvalidos')]
    public function test_validar_destacada_invalida_falla(
        mixed $destacada
    ): void {
        $this->assertSame(
            'El campo destacada es inválido',
            PropiedadValidator::validarDestacada($destacada)
        );
    }

    #[DataProvider('disponiblesValidos')]
    public function test_validar_destacada_ok(int $destacada): void
    {
        $this->assertNull(
            PropiedadValidator::validarDestacada($destacada)
        );
    }

    public function test_validar_categoria_id_null_falla(): void
    {
        $this->assertSame(
            'La categoría es obligatoria',
            PropiedadValidator::validarCategoriaId(null)
        );
    }

    #[DataProvider('idsDeEntidadInvalidos')]
    public function test_validar_categoria_id_invalida_falla(
        mixed $categoriaId
    ): void {
        $this->assertSame(
            'La categoría debe ser un ID válido',
            PropiedadValidator::validarCategoriaId($categoriaId)
        );
    }

    public static function idsDeEntidadInvalidos(): array
    {
        return [
            'cero' => [0],
            'negativo' => [-1],
            'cadena_numerica' => ['1'],
        ];
    }

    #[DataProvider('idsDeEntidadValidos')]
    public function test_validar_categoria_id_ok(int $categoriaId): void
    {
        $this->assertNull(
            PropiedadValidator::validarCategoriaId($categoriaId)
        );
    }

    public static function idsDeEntidadValidos(): array
    {
        return [
            'minimo' => [1],
            'varios' => [9],
        ];
    }

    public function test_validar_localidad_id_null_falla(): void
    {
        $this->assertSame(
            'La localidad es obligatoria',
            PropiedadValidator::validarLocalidadId(null)
        );
    }

    #[DataProvider('idsDeEntidadInvalidos')]
    public function test_validar_localidad_id_invalida_falla(
        mixed $localidadId
    ): void {
        $this->assertSame(
            'La localidad debe ser un ID válido',
            PropiedadValidator::validarLocalidadId($localidadId)
        );
    }

    #[DataProvider('idsDeEntidadValidos')]
    public function test_validar_localidad_id_ok(int $localidadId): void
    {
        $this->assertNull(
            PropiedadValidator::validarLocalidadId($localidadId)
        );
    }

    public function test_validar_precio_filtro_null_es_ok(): void
    {
        $this->assertNull(
            PropiedadValidator::validarPrecioFiltro(null)
        );
    }

    #[DataProvider('preciosFiltroInvalidos')]
    public function test_validar_precio_filtro_falla(
        mixed $precio,
        string $errorEsperado
    ): void {
        $this->assertSame(
            $errorEsperado,
            PropiedadValidator::validarPrecioFiltro($precio)
        );
    }

    public static function preciosFiltroInvalidos(): array
    {
        return [
            'texto' => ['caro', 'El precio debe ser numérico'],
            'negativo' => [
                -1,
                'El precio no puede ser negativo',
            ],
            'negativo_como_texto' => [
                '-5',
                'El precio no puede ser negativo',
            ],
        ];
    }

    #[DataProvider('preciosFiltroValidos')]
    public function test_validar_precio_filtro_ok(mixed $precio): void
    {
        $this->assertNull(
            PropiedadValidator::validarPrecioFiltro($precio)
        );
    }

    public static function preciosFiltroValidos(): array
    {
        return [
            'cero' => [0],
            'entero' => [1000],
            'decimal' => [99.5],
            'cadena_numerica' => ['500'],
        ];
    }

    public function test_validar_cantidad_filtro_null_es_ok(): void
    {
        $this->assertNull(
            PropiedadValidator::validarCantidadFiltro(null)
        );
    }

    #[DataProvider('cantidadesFiltroInvalidas')]
    public function test_validar_cantidad_filtro_falla(
        mixed $cantidad
    ): void {
        $this->assertSame(
            'La cantidad debe ser un entero mayor a 0',
            PropiedadValidator::validarCantidadFiltro($cantidad)
        );
    }

    public static function cantidadesFiltroInvalidas(): array
    {
        return [
            'texto' => ['varias'],
            'decimal' => [2.5],
            'decimal_como_texto' => ['2.5'],
            'cero' => [0],
            'cero_como_texto' => ['0'],
            'negativo' => [-3],
        ];
    }

    #[DataProvider('cantidadesFiltroValidas')]
    public function test_validar_cantidad_filtro_ok(
        mixed $cantidad
    ): void {
        $this->assertNull(
            PropiedadValidator::validarCantidadFiltro($cantidad)
        );
    }

    public static function cantidadesFiltroValidas(): array
    {
        return [
            'entero' => [2],
            'cadena_numerica' => ['2'],
        ];
    }

    public function test_validar_rango_precio_con_null_es_ok(): void
    {
        $this->assertNull(
            PropiedadValidator::validarRangoPrecio(null, null)
        );
        $this->assertNull(
            PropiedadValidator::validarRangoPrecio(100, null)
        );
        $this->assertNull(
            PropiedadValidator::validarRangoPrecio(null, 100)
        );
    }

    public function test_validar_rango_precio_min_mayor_que_max_falla(): void
    {
        $this->assertSame(
            'El precio mínimo no puede ser mayor que el precio máximo',
            PropiedadValidator::validarRangoPrecio(200, 100)
        );
    }

    #[DataProvider('rangosDePrecioValidos')]
    public function test_validar_rango_precio_ok(
        mixed $min,
        mixed $max
    ): void {
        $this->assertNull(
            PropiedadValidator::validarRangoPrecio($min, $max)
        );
    }

    public static function rangosDePrecioValidos(): array
    {
        return [
            'iguales' => [100, 100],
            'min_menor' => [50, 100],
            'como_texto' => ['50', '100'],
        ];
    }

    public function test_validar_ids_filtro_null_es_ok(): void
    {
        $this->assertNull(
            PropiedadValidator::validarIdsFiltro(null, 'categoria_id')
        );
        $this->assertNull(
            PropiedadValidator::validarIdsFiltro([], 'categoria_id')
        );
    }

    public function test_validar_ids_filtro_no_array_falla(): void
    {
        $this->assertSame(
            'El campo categoria_id debe ser un arreglo',
            PropiedadValidator::validarIdsFiltro('1', 'categoria_id')
        );
    }

    #[DataProvider('listasDeIdsInvalidas')]
    public function test_validar_ids_filtro_con_id_invalido_falla(
        array $ids
    ): void {
        $this->assertSame(
            'El campo servicio_id contiene un ID inválido',
            PropiedadValidator::validarIdsFiltro($ids, 'servicio_id')
        );
    }

    public static function listasDeIdsInvalidas(): array
    {
        return [
            'cero' => [[1, 0]],
            'negativo' => [[-1]],
            'texto' => [['a']],
            'decimal' => [[1.5]],
        ];
    }

    #[DataProvider('listasDeIdsValidas')]
    public function test_validar_ids_filtro_ok(array $ids): void
    {
        $this->assertNull(
            PropiedadValidator::validarIdsFiltro($ids, 'categoria_id')
        );
    }

    public static function listasDeIdsValidas(): array
    {
        return [
            'una' => [[1]],
            'varias' => [[1, 2, 3]],
            'como_texto' => [['1', '2']],
        ];
    }

    public function test_validar_filtros_ok(): void
    {
        $resultado = PropiedadValidator::validarFiltros([]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_filtros_con_filtros_validos_ok(): void
    {
        $resultado = PropiedadValidator::validarFiltros([
            'categoria_id' => [1, 2],
            'localidad_id' => [3],
            'servicio_id' => [4],
            'acepta_mascotas' => 1,
            'acepta_hijos' => 0,
            'precio_min' => 100,
            'precio_max' => 500,
            'cantidad_ambientes' => 2,
            'cantidad_dormitorios' => 1,
            'cantidad_banos' => 1,
            'capacidad' => 3,
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_filtros_con_varios_errores_acumula(): void
    {
        $resultado = PropiedadValidator::validarFiltros([
            'categoria_id' => 'no-array',
            'acepta_mascotas' => 7,
            'precio_min' => 'caro',
            'precio_max' => -1,
            'cantidad_ambientes' => 2.5,
            'precio_min_rango' => null,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'categoria_id' =>
                    'El campo categoria_id debe ser un arreglo',
                'acepta_mascotas' => 'El campo acepta_mascotas es inválido',
                'precio_min' => 'El precio debe ser numérico',
                'precio_max' => 'El precio no puede ser negativo',
                'cantidad_ambientes' =>
                    'La cantidad debe ser un entero mayor a 0',
                'rango_precio' =>
                    'El precio mínimo no puede ser mayor que el precio máximo',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_filtros_con_rango_de_precio_invalido_falla(): void
    {
        $resultado = PropiedadValidator::validarFiltros([
            'precio_min' => 500,
            'precio_max' => 100,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'rango_precio' =>
                    'El precio mínimo no puede ser mayor que el precio máximo',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_ok(): void
    {
        $resultado = PropiedadValidator::validar([
            'titulo' => 'Depto luminoso',
            'descripcion' => 'Muy lindo depto',
            'precio' => 50000,
            'expensas' => 10000,
            'direccion' => 'Av. Siempreviva 742',
            'cantidad_ambientes' => 2,
            'cantidad_dormitorios' => 1,
            'cantidad_banos' => 1,
            'capacidad' => 3,
            'acepta_mascotas' => 1,
            'acepta_hijos' => 0,
            'disponible' => 1,
            'destacada' => 0,
            'categoria_id' => 1,
            'localidad_id' => 2,
        ]);

        $this->assertTrue($resultado['success']);
        $this->assertSame('Validación exitosa', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    public function test_validar_sin_campos_acumula_los_requeridos(): void
    {
        $resultado = PropiedadValidator::validar([]);

        $this->assertFalse($resultado['success']);
        $this->assertSame('Error de validación', $resultado['message']);
        $this->assertSame(
            [
                'titulo' => 'El título es obligatorio',
                'precio' => 'El precio es obligatorio',
                'expensas' => 'Las expensas son obligatorias',
                'direccion' => 'La dirección es obligatoria',
                'cantidad_ambientes' =>
                    'La cantidad de ambientes es obligatoria',
                'cantidad_dormitorios' =>
                    'La cantidad de dormitorios es obligatoria',
                'cantidad_banos' => 'La cantidad de baños es obligatoria',
                'disponible' => 'El estado de disponibilidad es inválido',
                'destacada' => 'El campo destacada es inválido',
                'categoria_id' => 'La categoría es obligatoria',
                'localidad_id' => 'La localidad es obligatoria',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_con_dormitorios_mayores_a_ambientes_falla(): void
    {
        $resultado = PropiedadValidator::validar([
            'titulo' => 'Depto',
            'precio' => 1000,
            'expensas' => 0,
            'direccion' => 'Calle Falsa 123',
            'cantidad_ambientes' => 1,
            'cantidad_dormitorios' => 2,
            'cantidad_banos' => 1,
            'disponible' => 1,
            'destacada' => 0,
            'categoria_id' => 1,
            'localidad_id' => 1,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'cantidad_dormitorios' =>
                    'Los dormitorios no pueden superar la cantidad de ambientes',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_con_campos_booleanos_invalidos_falla(): void
    {
        $resultado = PropiedadValidator::validar([
            'titulo' => 'Depto',
            'precio' => 1000,
            'expensas' => 0,
            'direccion' => 'Calle Falsa 123',
            'cantidad_ambientes' => 1,
            'cantidad_dormitorios' => 1,
            'cantidad_banos' => 1,
            'acepta_mascotas' => 'quizas',
            'acepta_hijos' => 2,
            'disponible' => 5,
            'destacada' => null,
            'categoria_id' => 1,
            'localidad_id' => 1,
        ]);

        $this->assertFalse($resultado['success']);
        $this->assertSame(
            [
                'acepta_mascotas' => 'El campo acepta_mascotas es inválido',
                'acepta_hijos' => 'El campo acepta_hijos es inválido',
                'disponible' => 'El estado de disponibilidad es inválido',
                'destacada' => 'El campo destacada es inválido',
            ],
            $resultado['errors']
        );
    }

    public function test_validar_solo_id_ok(): void
    {
        $resultado = PropiedadValidator::validarSoloId(5);

        $this->assertTrue($resultado['success']);
        $this->assertSame('ID válido', $resultado['message']);
        $this->assertNull($resultado['errors']);
    }

    #[DataProvider('idsInvalidos')]
    public function test_validar_solo_id_falla(
        mixed $id,
        string $errorEsperado
    ): void {
        $resultado = PropiedadValidator::validarSoloId($id);

        $this->assertFalse($resultado['success']);
        $this->assertSame('ID inválido', $resultado['message']);
        $this->assertSame(['id' => $errorEsperado], $resultado['errors']);
    }
}
