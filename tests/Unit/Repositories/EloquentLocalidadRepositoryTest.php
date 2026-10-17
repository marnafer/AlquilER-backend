<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentLocalidadRepository;

final class EloquentLocalidadRepositoryTest extends RepositorioTestCase
{
    private EloquentLocalidadRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentLocalidadRepository();
    }

    public function test_all_ordena_por_id_desc_y_excluye_eliminados(): void
    {
        $this->insertar('localidades', [
            ['id' => 1, 'nombre' => 'A', 'provincia_id' => 1],
            ['id' => 2, 'nombre' => 'B', 'provincia_id' => 1],
            ['id' => 3, 'nombre' => 'C', 'provincia_id' => 1, 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame([2, 1], $this->repo->all()->pluck('id')->all());
        $this->assertSame(
            [3, 2, 1],
            $this->repo->all(['incluir_eliminados' => true])->pluck('id')->all()
        );
        $this->assertSame(
            [3],
            $this->repo->all(['solo_eliminados' => true])->pluck('id')->all()
        );
    }

    public function test_find_by_id_y_find_deleted_by_id(): void
    {
        $this->insertar('localidades', [
            ['id' => 1, 'nombre' => 'Activa', 'provincia_id' => 1],
            ['id' => 2, 'nombre' => 'Borrada', 'provincia_id' => 1, 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame('Activa', $this->repo->findById(1)?->nombre);
        $this->assertNull($this->repo->findById(2));
        $this->assertSame('Borrada', $this->repo->findDeletedById(2)?->nombre);
        $this->assertNull($this->repo->findDeletedById(1));
    }

    public function test_exists_by_name_in_province(): void
    {
        $this->insertar('localidades', [
            ['id' => 1, 'nombre' => 'Centro', 'provincia_id' => 1],
            ['id' => 2, 'nombre' => 'Centro', 'provincia_id' => 2],
        ]);

        $this->assertTrue($this->repo->existsByNameInProvince('Centro', 1));
        $this->assertTrue($this->repo->existsByNameInProvince('Centro', 2));
        $this->assertFalse($this->repo->existsByNameInProvince('Centro', 3));
        $this->assertFalse($this->repo->existsByNameInProvince('Norte', 1));
        $this->assertFalse($this->repo->existsByNameInProvince('Centro', 1, 1));
        $this->assertTrue($this->repo->existsByNameInProvince('Centro', 1, 2));
    }

    public function test_has_properties(): void
    {
        $this->insertar('localidades', [
            ['id' => 1, 'nombre' => 'Con props', 'provincia_id' => 1],
            ['id' => 2, 'nombre' => 'Sin props', 'provincia_id' => 1],
        ]);
        $this->insertar('propiedades', [
            ['id' => 10, 'localidad_id' => 1],
        ]);

        $this->assertTrue($this->repo->hasProperties($this->repo->findById(1)));
        $this->assertFalse($this->repo->hasProperties($this->repo->findById(2)));
    }

    public function test_create_update_delete_y_restore(): void
    {
        $localidad = $this->repo->create([
            'nombre' => 'Nueva',
            'codigo_postal' => '2000',
            'provincia_id' => 1,
        ]);

        $this->assertSame('Nueva', $localidad->fresh()->nombre);
        $this->assertTrue($this->repo->update($localidad, ['nombre' => 'Editada']));
        $this->assertSame('Editada', $localidad->fresh()->nombre);

        $this->assertTrue($this->repo->delete($localidad));
        $this->assertNull($this->repo->findById($localidad->id));

        $this->assertTrue(
            $this->repo->restore($this->repo->findDeletedById($localidad->id))
        );
        $this->assertNotNull($this->repo->findById($localidad->id));
    }
}
