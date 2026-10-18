<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentPropiedadImagenRepository;

final class EloquentPropiedadImagenRepositoryTest extends RepositorioTestCase
{
    private EloquentPropiedadImagenRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentPropiedadImagenRepository();
    }

    private function sembrar(): void
    {
        $this->insertar('propiedades', [
            ['id' => 10, 'titulo' => 'Casa'],
            ['id' => 20, 'titulo' => 'Depto'],
        ]);
        $this->insertar('propiedad_imagenes', [
            ['id' => 1, 'propiedad_id' => 10, 'ruta' => 'a.jpg', 'es_principal' => 0],
            ['id' => 2, 'propiedad_id' => 10, 'ruta' => 'b.jpg', 'es_principal' => 1],
            ['id' => 3, 'propiedad_id' => 20, 'ruta' => 'c.jpg', 'es_principal' => 1],
        ]);
    }

    public function test_all_ordena_desc_y_carga_propiedad(): void
    {
        $this->sembrar();

        $imagenes = $this->repo->all();

        $this->assertSame([3, 2, 1], $imagenes->pluck('id')->all());
        $this->assertTrue($imagenes->first()->relationLoaded('propiedad'));
    }

    public function test_find_by_id(): void
    {
        $this->sembrar();

        $imagen = $this->repo->findById(2);

        $this->assertSame('b.jpg', $imagen->ruta);
        $this->assertSame(10, $imagen->propiedad->id);
        $this->assertNull($this->repo->findById(99));
    }

    public function test_find_by_propiedad_id_y_count(): void
    {
        $this->sembrar();

        $this->assertSame(
            [2, 1],
            $this->repo->findByPropiedadId(10)->pluck('id')->all()
        );
        $this->assertSame(2, $this->repo->countByPropiedadId(10));
        $this->assertSame(0, $this->repo->countByPropiedadId(99));
    }

    public function test_create(): void
    {
        $this->sembrar();

        $imagen = $this->repo->create([
            'propiedad_id' => 10,
            'ruta' => 'nueva.jpg',
            'es_principal' => 0,
        ]);

        $this->assertNotNull($imagen->id);
        $this->assertSame(3, $this->repo->countByPropiedadId(10));
    }

    public function test_clear_principal_solo_afecta_a_su_propiedad(): void
    {
        $this->sembrar();

        $this->repo->clearPrincipalByPropiedadId(10);

        $this->assertSame(
            0,
            (int) $this->capsule
                ->table('propiedad_imagenes')
                ->where('id', 2)
                ->value('es_principal')
        );
        $this->assertSame(
            1,
            (int) $this->capsule
                ->table('propiedad_imagenes')
                ->where('id', 3)
                ->value('es_principal')
        );
    }

    public function test_set_principal(): void
    {
        $this->sembrar();

        $imagen = $this->repo->findById(1);
        $this->repo->setPrincipal($imagen);

        $this->assertSame(1, (int) $imagen->fresh()->es_principal);
    }

    public function test_delete(): void
    {
        $this->sembrar();

        $this->repo->delete($this->repo->findById(1));

        $this->assertNull($this->repo->findById(1));
        $this->assertSame(1, $this->repo->countByPropiedadId(10));
    }

    public function test_recorrer_rutas_usa_todas_las_imagenes_en_orden(): void
    {
        $this->sembrar();

        $rutas = [];
        $this->repo->recorrerRutas(function (string $ruta) use (&$rutas): void {
            $rutas[] = $ruta;
        });

        $this->assertSame(['a.jpg', 'b.jpg', 'c.jpg'], $rutas);
    }
}
