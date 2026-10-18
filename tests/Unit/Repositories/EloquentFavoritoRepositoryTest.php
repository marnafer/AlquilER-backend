<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentFavoritoRepository;

final class EloquentFavoritoRepositoryTest extends RepositorioTestCase
{
    private EloquentFavoritoRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentFavoritoRepository();
    }

    private function sembrar(): void
    {
        $this->insertar('categorias', [['id' => 1, 'nombre' => 'Casa']]);
        $this->insertar('localidades', [
            ['id' => 1, 'nombre' => 'Centro', 'provincia_id' => 1],
        ]);
        $this->insertar('propiedades', [
            ['id' => 10, 'categoria_id' => 1, 'localidad_id' => 1],
            ['id' => 20, 'categoria_id' => 1, 'localidad_id' => 1],
        ]);
        $this->insertar('propiedad_imagenes', [
            ['id' => 1, 'propiedad_id' => 10, 'ruta' => 'p.jpg', 'es_principal' => 1],
        ]);
        $this->insertar('favoritos', [
            ['id' => 1, 'usuario_id' => 1, 'propiedad_id' => 10],
            ['id' => 2, 'usuario_id' => 1, 'propiedad_id' => 20],
            ['id' => 3, 'usuario_id' => 2, 'propiedad_id' => 10],
        ]);
    }

    public function test_get_by_user_id_carga_relaciones_y_ordena_desc(): void
    {
        $this->sembrar();

        $favoritos = $this->repo->getByUserId(1);

        $this->assertSame([2, 1], array_column($favoritos, 'id'));
        $this->assertArrayHasKey('categoria', $favoritos[0]['propiedad']);
        $this->assertArrayHasKey('localidad', $favoritos[0]['propiedad']);
        $this->assertArrayHasKey('imagenes', $favoritos[0]['propiedad']);

        $delPrimero = $favoritos[1];
        $this->assertSame('p.jpg', $delPrimero['propiedad']['imagenes'][0]['ruta']);
    }

    public function test_find_by_usuario_and_propiedad_y_exists(): void
    {
        $this->sembrar();

        $this->assertNotNull($this->repo->findByUsuarioAndPropiedad(1, 10));
        $this->assertNull($this->repo->findByUsuarioAndPropiedad(1, 99));
        $this->assertTrue($this->repo->exists(1, 10));
        $this->assertFalse($this->repo->exists(1, 99));
    }

    public function test_add_y_remove(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->add(1, 30));
        $this->assertFalse($this->repo->add(1, 30));
        $this->assertTrue($this->repo->exists(1, 30));

        $this->assertTrue($this->repo->remove(1, 30));
        $this->assertFalse($this->repo->remove(1, 30));
    }

    public function test_count_by_propiedad_y_get_ids(): void
    {
        $this->sembrar();

        $this->assertSame(2, $this->repo->countByPropiedad(10));
        $this->assertSame(0, $this->repo->countByPropiedad(99));
        $this->assertSame([10, 20], $this->repo->getPropiedadIdsByUser(1));
    }
}
