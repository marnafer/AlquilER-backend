<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentPropiedadRepository;

final class EloquentPropiedadRepositoryTest extends RepositorioTestCase
{
    private EloquentPropiedadRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentPropiedadRepository();
    }

    private function sembrar(): void
    {
        $this->insertar('categorias', [
            ['id' => 1, 'nombre' => 'A'],
            ['id' => 2, 'nombre' => 'B'],
        ]);
        $this->insertar('localidades', [
            ['id' => 1, 'nombre' => 'L1', 'provincia_id' => 1],
            ['id' => 2, 'nombre' => 'L2', 'provincia_id' => 1],
        ]);
        $this->insertar('servicios', [
            ['id' => 1, 'nombre' => 'Luz'],
            ['id' => 2, 'nombre' => 'Agua'],
        ]);
        $this->insertar('propiedades', [
            ['id' => 1, 'titulo' => 'Casa', 'categoria_id' => 1, 'localidad_id' => 1, 'usuario_id' => 1, 'precio' => 100, 'cantidad_ambientes' => 2, 'cantidad_dormitorios' => 1, 'cantidad_banos' => 1, 'capacidad' => 2, 'destacada' => 1, 'acepta_mascotas' => 1, 'acepta_hijos' => 0, 'disponible' => 1],
            ['id' => 2, 'titulo' => 'Depto', 'categoria_id' => 2, 'localidad_id' => 2, 'usuario_id' => 1, 'precio' => 200, 'cantidad_ambientes' => 4, 'cantidad_dormitorios' => 3, 'cantidad_banos' => 2, 'capacidad' => 4, 'destacada' => 0, 'acepta_mascotas' => 0, 'acepta_hijos' => 1, 'disponible' => 1],
            ['id' => 3, 'titulo' => 'Vieja', 'categoria_id' => 1, 'localidad_id' => 1, 'usuario_id' => 2, 'precio' => 300, 'cantidad_ambientes' => 5, 'cantidad_dormitorios' => 3, 'cantidad_banos' => 2, 'capacidad' => 6, 'destacada' => 1, 'disponible' => 0, 'deleted_at' => '2026-01-01 00:00:00'],
        ]);
        $this->insertar('propiedad_servicio', [
            ['propiedad_id' => 1, 'servicio_id' => 1],
            ['propiedad_id' => 1, 'servicio_id' => 2],
            ['propiedad_id' => 2, 'servicio_id' => 1],
        ]);
        $this->insertar('propiedad_imagenes', [
            ['id' => 1, 'propiedad_id' => 1, 'ruta' => 'principal.jpg', 'es_principal' => 1],
            ['id' => 2, 'propiedad_id' => 1, 'ruta' => 'otra.jpg', 'es_principal' => 0],
        ]);
    }

    public function test_all_ordena_asc_carga_relaciones_y_excluye_eliminados(): void
    {
        $this->sembrar();

        $propiedades = $this->repo->all();

        $this->assertSame([1, 2], $propiedades->pluck('id')->all());
        $this->assertTrue($propiedades->first()->relationLoaded('imagenes'));
        $this->assertTrue($propiedades->first()->relationLoaded('imagenPrincipal'));
        $this->assertTrue($propiedades->first()->relationLoaded('servicios'));
    }

    public function test_all_filtra_por_categoria_y_localidad(): void
    {
        $this->sembrar();

        $this->assertSame([1], $this->repo->all(['categoria_id' => [1]])->pluck('id')->all());
        $this->assertSame([2], $this->repo->all(['localidad_id' => [2]])->pluck('id')->all());
    }

    public function test_all_filtra_por_servicios_de_forma_conjuntiva(): void
    {
        $this->sembrar();

        $this->assertSame([1, 2], $this->repo->all(['servicio_id' => [1]])->pluck('id')->all());
        $this->assertSame([1], $this->repo->all(['servicio_id' => [1, 2]])->pluck('id')->all());
    }

    public function test_all_filtra_por_rango_de_precio(): void
    {
        $this->sembrar();

        $this->assertSame([2], $this->repo->all(['precio_min' => 150])->pluck('id')->all());
        $this->assertSame([1], $this->repo->all(['precio_max' => 150])->pluck('id')->all());
        $this->assertSame([2], $this->repo->all(['precio_min' => 150, 'precio_max' => 250])->pluck('id')->all());
    }

    public function test_all_filtra_por_cantidades(): void
    {
        $this->sembrar();

        $this->assertSame([2], $this->repo->all(['cantidad_ambientes' => 3])->pluck('id')->all());
        $this->assertSame([2], $this->repo->all(['cantidad_dormitorios' => 3])->pluck('id')->all());
        $this->assertSame([2], $this->repo->all(['cantidad_banos' => 2])->pluck('id')->all());
        $this->assertSame([2], $this->repo->all(['capacidad' => 4])->pluck('id')->all());
    }

    public function test_all_filtra_por_banderas(): void
    {
        $this->sembrar();

        $this->assertSame([1], $this->repo->all(['destacada' => true])->pluck('id')->all());
        $this->assertSame([1], $this->repo->all(['acepta_mascotas' => true])->pluck('id')->all());
        $this->assertSame([2], $this->repo->all(['acepta_hijos' => true])->pluck('id')->all());
        $this->assertSame([1, 2], $this->repo->all(['disponible' => true])->pluck('id')->all());
    }

    public function test_all_para_admin_incluye_eliminados(): void
    {
        $this->sembrar();

        $this->assertSame([2, 1], $this->repo->allParaAdmin()->pluck('id')->all());
        $this->assertSame([3], $this->repo->allParaAdmin(['solo_eliminados' => true])->pluck('id')->all());
        $this->assertSame([3, 2, 1], $this->repo->allParaAdmin(['incluir_eliminados' => true])->pluck('id')->all());
    }

    public function test_por_usuario_y_existe_por_usuario(): void
    {
        $this->sembrar();

        $this->assertSame([1, 2], $this->repo->porUsuario(1)->pluck('id')->all());
        $this->assertTrue($this->repo->porUsuario(1)->first()->relationLoaded('servicios'));
        $this->assertSame([], $this->repo->porUsuario(2)->pluck('id')->all());

        $this->assertTrue($this->repo->existePorUsuario(1));
        $this->assertFalse($this->repo->existePorUsuario(2));
    }

    public function test_find_by_id_con_relaciones_y_find_deleted(): void
    {
        $this->sembrar();

        $propiedad = $this->repo->findById(1);

        $this->assertNotNull($propiedad);
        $this->assertTrue($propiedad->relationLoaded('categoria'));
        $this->assertTrue($propiedad->relationLoaded('localidad'));
        $this->assertTrue($propiedad->relationLoaded('imagenes'));
        $this->assertSame('principal.jpg', $propiedad->imagen_url);

        $this->assertNull($this->repo->findById(3));
        $this->assertNotNull($this->repo->findDeletedById(3));
    }

    public function test_find_by_id_for_update(): void
    {
        $this->sembrar();

        $propiedad = $this->repo->findByIdForUpdate(1);

        $this->assertNotNull($propiedad);
        $this->assertSame(1, $propiedad->id);
        $this->assertNull($this->repo->findByIdForUpdate(3));
        $this->assertNull($this->repo->findByIdForUpdate(99));
    }

    public function test_create_update_delete_y_restore(): void
    {
        $this->sembrar();

        $propiedad = $this->repo->create(['titulo' => 'Nueva', 'categoria_id' => 1]);
        $this->assertNotNull($propiedad->id);
        $this->assertNull($propiedad->fresh()->imagen_url);

        $this->assertTrue($this->repo->update($propiedad, ['titulo' => 'Editada']));
        $this->assertSame('Editada', $propiedad->fresh()->titulo);

        $this->assertTrue($this->repo->delete($propiedad));
        $this->assertNull($this->repo->findById($propiedad->id));

        $this->assertTrue($this->repo->restore($propiedad));
        $this->assertNotNull($this->repo->findById($propiedad->id));
    }

    public function test_all_con_limit_ordena_desc_y_limita(): void
    {
        $this->sembrar();

        $this->assertSame(
            [2],
            $this->repo->all(['limit' => 1])->pluck('id')->all()
        );
    }

    public function test_paginate_ordena_por_defecto_y_pagina(): void
    {
        $this->sembrar();

        $primera = $this->repo->paginate([], 1, 1);

        $this->assertSame([2], $primera['items']->pluck('id')->all());
        $this->assertSame(2, $primera['total']);
        $this->assertSame(
            ['current_page' => 1, 'per_page' => 1, 'last_page' => 2],
            $primera['pagination']
        );

        $segunda = $this->repo->paginate([], 2, 1);

        $this->assertSame([1], $segunda['items']->pluck('id')->all());
    }

    public function test_paginate_ordena_por_precio_asc(): void
    {
        $this->sembrar();

        $resultado = $this->repo->paginate(['orden' => 'precio_asc'], 1, 10);

        $this->assertSame([1, 2], $resultado['items']->pluck('id')->all());
    }

    public function test_paginate_con_orden_invalido_usa_el_por_defecto(): void
    {
        $this->sembrar();

        $resultado = $this->repo->paginate(['orden' => 'inexistente'], 1, 10);

        $this->assertSame([2, 1], $resultado['items']->pluck('id')->all());
    }

    public function test_paginate_aplica_filtros(): void
    {
        $this->sembrar();

        $resultado = $this->repo->paginate(['categoria_id' => [2]], 1, 10);

        $this->assertSame([2], $resultado['items']->pluck('id')->all());
        $this->assertSame(1, $resultado['total']);
        $this->assertSame(1, $resultado['pagination']['last_page']);
    }
}
