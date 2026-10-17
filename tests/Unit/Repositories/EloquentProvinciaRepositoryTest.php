<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentProvinciaRepository;

final class EloquentProvinciaRepositoryTest extends RepositorioTestCase
{
    private EloquentProvinciaRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentProvinciaRepository();
    }

    public function test_all_ordena_por_nombre_y_excluye_eliminados(): void
    {
        $this->insertar('provincias', [
            ['id' => 1, 'nombre' => 'Santa Fe'],
            ['id' => 2, 'nombre' => 'Buenos Aires'],
            ['id' => 3, 'nombre' => 'Cordoba', 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame(
            ['Buenos Aires', 'Santa Fe'],
            $this->repo->all()->pluck('nombre')->all()
        );
    }

    public function test_all_incluye_y_solo_eliminados(): void
    {
        $this->insertar('provincias', [
            ['id' => 1, 'nombre' => 'Activa'],
            ['id' => 2, 'nombre' => 'Borrada', 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame(
            [1, 2],
            $this->repo->all(['incluir_eliminados' => true])
                ->pluck('id')->sort()->values()->all()
        );
        $this->assertSame(
            [2],
            $this->repo->all(['solo_eliminados' => true])->pluck('id')->all()
        );
    }

    public function test_find_by_id_y_find_deleted_by_id(): void
    {
        $this->insertar('provincias', [
            ['id' => 1, 'nombre' => 'Activa'],
            ['id' => 2, 'nombre' => 'Borrada', 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame('Activa', $this->repo->findById(1)?->nombre);
        $this->assertNull($this->repo->findById(2));
        $this->assertSame('Borrada', $this->repo->findDeletedById(2)?->nombre);
        $this->assertNull($this->repo->findDeletedById(1));
    }

    public function test_exists_by_name_respeta_except_id(): void
    {
        $this->insertar('provincias', [
            ['id' => 1, 'nombre' => 'Mendoza'],
        ]);

        $this->assertTrue($this->repo->existsByName('Mendoza'));
        $this->assertFalse($this->repo->existsByName('Salta'));
        $this->assertFalse($this->repo->existsByName('Mendoza', 1));
        $this->insertar('provincias', [['id' => 2, 'nombre' => 'Mendoza']]);
        $this->assertTrue($this->repo->existsByName('Mendoza', 1));
    }

    public function test_has_localities(): void
    {
        $this->insertar('provincias', [
            ['id' => 1, 'nombre' => 'Con localidades'],
            ['id' => 2, 'nombre' => 'Sin localidades'],
        ]);
        $this->insertar('localidades', [
            ['id' => 5, 'nombre' => 'Rosario', 'provincia_id' => 1],
        ]);

        $this->assertTrue($this->repo->hasLocalities($this->repo->findById(1)));
        $this->assertFalse($this->repo->hasLocalities($this->repo->findById(2)));
    }

    public function test_create_update_delete_y_restore(): void
    {
        $provincia = $this->repo->create(['nombre' => 'Nueva']);

        $this->assertSame('Nueva', $provincia->fresh()->nombre);
        $this->assertTrue($this->repo->update($provincia, ['nombre' => 'Editada']));
        $this->assertSame('Editada', $provincia->fresh()->nombre);

        $this->assertTrue($this->repo->delete($provincia));
        $this->assertNull($this->repo->findById($provincia->id));

        $this->assertTrue(
            $this->repo->restore($this->repo->findDeletedById($provincia->id))
        );
        $this->assertNotNull($this->repo->findById($provincia->id));
    }
}
