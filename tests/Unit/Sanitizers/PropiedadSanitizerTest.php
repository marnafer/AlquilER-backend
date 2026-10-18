<?php

declare(strict_types=1);

namespace Tests\Unit\Sanitizers;

use App\Sanitizers\PropiedadSanitizer;
use Tests\TestCase;

class PropiedadSanitizerTest extends TestCase
{
    public function test_sanitizar_crear_full_payload(): void
    {
        $resultado = PropiedadSanitizer::sanitizarCrear([
            'titulo' => '  Casa   grande ',
            'descripcion' => '  Descripcion ',
            'precio' => '100,5',
            'expensas' => '10',
            'direccion' => '  Calle 123 ',
            'cantidad_ambientes' => '3',
            'cantidad_dormitorios' => '2',
            'cantidad_banos' => '1',
            'capacidad' => '4',
            'acepta_mascotas' => 'yes',
            'acepta_hijos' => 'no',
            'requisitos_interesados' => ['max_ocupantes' => '2'],
            'disponible' => 'yes',
            'destacada' => 'no',
            'categoria_id' => '1',
            'localidad_id' => '2',
        ]);

        $this->assertSame('Casa grande', $resultado['titulo']);
        $this->assertSame('Descripcion', $resultado['descripcion']);
        $this->assertSame(100.5, $resultado['precio']);
        $this->assertSame(10.0, $resultado['expensas']);
        $this->assertSame('Calle 123', $resultado['direccion']);
        $this->assertSame(3, $resultado['cantidad_ambientes']);
        $this->assertSame(2, $resultado['cantidad_dormitorios']);
        $this->assertSame(1, $resultado['cantidad_banos']);
        $this->assertSame(4, $resultado['capacidad']);
        $this->assertSame(1, $resultado['acepta_mascotas']);
        $this->assertSame(0, $resultado['acepta_hijos']);
        $this->assertSame(['max_ocupantes' => '2'], $resultado['requisitos_interesados']);
        $this->assertSame(1, $resultado['disponible']);
        $this->assertSame(0, $resultado['destacada']);
        $this->assertSame(1, $resultado['categoria_id']);
        $this->assertSame(2, $resultado['localidad_id']);
    }

    public function test_sanitizar_crear_empty_payload(): void
    {
        $resultado = PropiedadSanitizer::sanitizarCrear([]);

        $this->assertNull($resultado['titulo']);
        $this->assertNull($resultado['descripcion']);
        $this->assertNull($resultado['precio']);
        $this->assertSame(0.0, $resultado['expensas']);
        $this->assertNull($resultado['direccion']);
        $this->assertNull($resultado['cantidad_ambientes']);
        $this->assertSame(1, $resultado['disponible']);
        $this->assertSame(0, $resultado['destacada']);
        $this->assertSame([], $resultado['requisitos_interesados']);
    }

    public function test_sanitizar_actualizar_all_fields(): void
    {
        $resultado = PropiedadSanitizer::sanitizarActualizar([
            'titulo' => 'titulo',
            'descripcion' => 'descripcion',
            'precio' => '100',
            'expensas' => '50',
            'direccion' => 'direccion',
            'cantidad_ambientes' => 2,
            'cantidad_dormitorios' => 3,
            'cantidad_banos' => 1,
            'capacidad' => 4,
            'acepta_mascotas' => 'yes',
            'acepta_hijos' => 'no',
            'requisitos_interesados' => ['fecha_disponible_desde' => '2024-01-01'],
            'disponible' => 'yes',
            'destacada' => 'no',
            'categoria_id' => 1,
            'localidad_id' => 2,
        ]);

        $this->assertSame('titulo', $resultado['titulo']);
        $this->assertSame('descripcion', $resultado['descripcion']);
        $this->assertSame(100.0, $resultado['precio']);
        $this->assertSame(50.0, $resultado['expensas']);
        $this->assertSame('direccion', $resultado['direccion']);
        $this->assertSame(2, $resultado['cantidad_ambientes']);
        $this->assertSame(3, $resultado['cantidad_dormitorios']);
        $this->assertSame(1, $resultado['cantidad_banos']);
        $this->assertSame(4, $resultado['capacidad']);
        $this->assertSame(1, $resultado['acepta_mascotas']);
        $this->assertSame(0, $resultado['acepta_hijos']);
        $this->assertSame(
            ['fecha_disponible_desde' => '2024-01-01'],
            $resultado['requisitos_interesados']
        );
        $this->assertSame(1, $resultado['disponible']);
        $this->assertSame(0, $resultado['destacada']);
        $this->assertSame(1, $resultado['categoria_id']);
        $this->assertSame(2, $resultado['localidad_id']);
    }

    public function test_sanitizar_actualizar_empty_returns_empty(): void
    {
        $this->assertSame([], PropiedadSanitizer::sanitizarActualizar([]));
    }

    public function test_sanitizar_filtros_all_keys(): void
    {
        $resultado = PropiedadSanitizer::sanitizarFiltros([
            'categoria_id' => '1',
            'localidad_id' => '2',
            'servicio_id' => '3',
            'acepta_mascotas' => 'yes',
            'acepta_hijos' => 'no',
            'precio_min' => '10',
            'precio_max' => '20',
            'cantidad_ambientes' => '2',
            'cantidad_dormitorios' => '3',
            'cantidad_banos' => '1',
            'capacidad' => '4',
            'limit' => '10',
            'page' => '1',
            'per_page' => '5',
        ]);

        $this->assertSame([1], $resultado['categoria_id']);
        $this->assertSame([2], $resultado['localidad_id']);
        $this->assertSame([3], $resultado['servicio_id']);
        $this->assertSame(1, $resultado['acepta_mascotas']);
        $this->assertSame(0, $resultado['acepta_hijos']);
        $this->assertSame('10', $resultado['precio_min']);
        $this->assertSame('20', $resultado['precio_max']);
        $this->assertSame('2', $resultado['cantidad_ambientes']);
        $this->assertSame('3', $resultado['cantidad_dormitorios']);
        $this->assertSame('1', $resultado['cantidad_banos']);
        $this->assertSame('4', $resultado['capacidad']);
        $this->assertSame(10, $resultado['limit']);
        $this->assertSame(1, $resultado['page']);
        $this->assertSame(5, $resultado['per_page']);
    }

    public function test_sanitizar_filtros_empty_returns_empty(): void
    {
        $this->assertSame([], PropiedadSanitizer::sanitizarFiltros([]));
    }

    public function test_sanitizar_limit_non_string_is_returned_as_is(): void
    {
        $resultado = PropiedadSanitizer::sanitizarFiltros(['limit' => 10]);

        $this->assertSame(10, $resultado['limit']);
    }

    public function test_sanitizar_limit_non_numeric_string_is_returned_as_is(): void
    {
        $resultado = PropiedadSanitizer::sanitizarFiltros(['limit' => 'abc']);

        $this->assertSame('abc', $resultado['limit']);
    }

    public function test_sanitizar_limit_trims_numeric_string(): void
    {
        $resultado = PropiedadSanitizer::sanitizarFiltros(['limit' => '  10  ']);

        $this->assertSame(10, $resultado['limit']);
    }

    public function test_sanitizar_precio_filtro(): void
    {
        $this->assertNull(PropiedadSanitizer::sanitizarPrecioFiltro(null));
        $this->assertNull(PropiedadSanitizer::sanitizarPrecioFiltro(''));
        $this->assertSame('1.5', PropiedadSanitizer::sanitizarPrecioFiltro('1,5'));
        $this->assertSame('10', PropiedadSanitizer::sanitizarPrecioFiltro(' 10 '));
    }

    public function test_sanitizar_cantidad_filtro(): void
    {
        $this->assertNull(PropiedadSanitizer::sanitizarCantidadFiltro(null));
        $this->assertNull(PropiedadSanitizer::sanitizarCantidadFiltro(''));
        $this->assertSame('3', PropiedadSanitizer::sanitizarCantidadFiltro(' 3 '));
    }

    public function test_sanitizar_ids_filtro_single_value(): void
    {
        $this->assertSame([5], PropiedadSanitizer::sanitizarIdsFiltro(5));
    }

    public function test_sanitizar_ids_filtro_mixed_values_and_dedupe(): void
    {
        $this->assertSame(
            [null, 3, 4],
            PropiedadSanitizer::sanitizarIdsFiltro([null, '', '3', 4, '3'])
        );
    }

    public function test_sanitizar_ids_filtro_keeps_non_numeric(): void
    {
        $this->assertSame(['abc'], PropiedadSanitizer::sanitizarIdsFiltro(['abc']));
    }

    public function test_sanitizar_id(): void
    {
        $this->assertNull(PropiedadSanitizer::sanitizarId(null));
        $this->assertNull(PropiedadSanitizer::sanitizarId(''));
        $this->assertSame(5, PropiedadSanitizer::sanitizarId('5'));
        $this->assertNull(PropiedadSanitizer::sanitizarId('abc'));
        $this->assertNull(PropiedadSanitizer::sanitizarId('0'));
    }

    public function test_sanitizar_titulo(): void
    {
        $this->assertNull(PropiedadSanitizer::sanitizarTitulo(123));
        $this->assertSame('Hola mundo', PropiedadSanitizer::sanitizarTitulo('  Hola   mundo '));
        $this->assertSame(
            150,
            mb_strlen(PropiedadSanitizer::sanitizarTitulo(str_repeat('a', 200)))
        );
    }

    public function test_sanitizar_descripcion(): void
    {
        $this->assertNull(PropiedadSanitizer::sanitizarDescripcion(123));
        $this->assertSame('Una descripcion', PropiedadSanitizer::sanitizarDescripcion('  Una   descripcion '));
    }

    public function test_sanitizar_precio(): void
    {
        $this->assertNull(PropiedadSanitizer::sanitizarPrecio(null));
        $this->assertNull(PropiedadSanitizer::sanitizarPrecio(''));
        $this->assertNull(PropiedadSanitizer::sanitizarPrecio('abc'));
        $this->assertSame(100.5, PropiedadSanitizer::sanitizarPrecio('100,5'));
    }

    public function test_sanitizar_expensas(): void
    {
        $this->assertSame(0.0, PropiedadSanitizer::sanitizarExpensas(null));
        $this->assertSame(0.0, PropiedadSanitizer::sanitizarExpensas(''));
        $this->assertSame(10.5, PropiedadSanitizer::sanitizarExpensas('10,5'));
        $this->assertNull(PropiedadSanitizer::sanitizarExpensas('abc'));
    }

    public function test_sanitizar_direccion(): void
    {
        $this->assertNull(PropiedadSanitizer::sanitizarDireccion(123));
        $this->assertSame('Calle 123', PropiedadSanitizer::sanitizarDireccion('  Calle   123 '));
        $this->assertSame(
            125,
            mb_strlen(PropiedadSanitizer::sanitizarDireccion(str_repeat('a', 200)))
        );
    }

    public function test_sanitizar_entero_positivo(): void
    {
        $this->assertNull(PropiedadSanitizer::sanitizarEnteroPositivo(null));
        $this->assertNull(PropiedadSanitizer::sanitizarEnteroPositivo(''));
        $this->assertSame(5, PropiedadSanitizer::sanitizarEnteroPositivo('5'));
        $this->assertNull(PropiedadSanitizer::sanitizarEnteroPositivo('abc'));
        $this->assertNull(PropiedadSanitizer::sanitizarEnteroPositivo('0'));
    }

    public function test_sanitizar_disponible(): void
    {
        $this->assertSame(1, PropiedadSanitizer::sanitizarDisponible(null));
        $this->assertSame(1, PropiedadSanitizer::sanitizarDisponible(''));
        $this->assertSame(1, PropiedadSanitizer::sanitizarDisponible('yes'));
        $this->assertSame(0, PropiedadSanitizer::sanitizarDisponible('no'));
        $this->assertNull(PropiedadSanitizer::sanitizarDisponible('quizas'));
    }

    public function test_sanitizar_booleano(): void
    {
        $this->assertNull(PropiedadSanitizer::sanitizarBooleano(null));
        $this->assertSame(1, PropiedadSanitizer::sanitizarBooleano(null, true));
        $this->assertSame(0, PropiedadSanitizer::sanitizarBooleano('', false));
        $this->assertSame(1, PropiedadSanitizer::sanitizarBooleano('yes'));
        $this->assertSame(0, PropiedadSanitizer::sanitizarBooleano('no'));
        $this->assertNull(PropiedadSanitizer::sanitizarBooleano('quizas'));
    }

    public function test_sanitizar_destacada(): void
    {
        $this->assertSame(0, PropiedadSanitizer::sanitizarDestacada(null));
        $this->assertSame(0, PropiedadSanitizer::sanitizarDestacada(''));
        $this->assertSame(1, PropiedadSanitizer::sanitizarDestacada('yes'));
        $this->assertSame(0, PropiedadSanitizer::sanitizarDestacada('no'));
        $this->assertNull(PropiedadSanitizer::sanitizarDestacada('quizas'));
    }

    public function test_sanitizar_requisitos_non_array_is_returned(): void
    {
        $this->assertSame('x', PropiedadSanitizer::sanitizarRequisitosInteresados('x'));
    }

    public function test_sanitizar_requisitos_fecha_disponible_desde(): void
    {
        $this->assertSame(
            ['fecha_disponible_desde' => '2024-01-01'],
            PropiedadSanitizer::sanitizarRequisitosInteresados([
                'fecha_disponible_desde' => ' 2024-01-01 ',
            ])
        );
    }

    public function test_sanitizar_requisitos_fecha_disponible_desde_non_string(): void
    {
        $this->assertSame(
            ['fecha_disponible_desde' => 123],
            PropiedadSanitizer::sanitizarRequisitosInteresados([
                'fecha_disponible_desde' => 123,
            ])
        );
    }

    public function test_sanitizar_requisitos_max_ocupantes(): void
    {
        $this->assertSame(
            ['max_ocupantes' => '3'],
            PropiedadSanitizer::sanitizarRequisitosInteresados(['max_ocupantes' => ' 3 '])
        );
        $this->assertSame(
            ['max_ocupantes' => 3],
            PropiedadSanitizer::sanitizarRequisitosInteresados(['max_ocupantes' => 3])
        );
    }

    public function test_sanitizar_requisitos_garantias_array(): void
    {
        $this->assertSame(
            ['garantias_aceptadas' => ['propietario', 5]],
            PropiedadSanitizer::sanitizarRequisitosInteresados([
                'garantias_aceptadas' => ['  propietario ', 5],
            ])
        );
    }

    public function test_sanitizar_requisitos_garantias_non_array(): void
    {
        $this->assertSame(
            ['garantias_aceptadas' => 'sin-garantias'],
            PropiedadSanitizer::sanitizarRequisitosInteresados([
                'garantias_aceptadas' => 'sin-garantias',
            ])
        );
    }

    public function test_sanitizar_requisitos_empty(): void
    {
        $this->assertSame([], PropiedadSanitizer::sanitizarRequisitosInteresados([]));
    }
}
