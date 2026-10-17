<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentCategoriaRepository;

final class EloquentCategoriaRepositoryTest extends RepositorioTestCase
{
    private EloquentCategoriaRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentCategoriaRepository();
    }

    public function test_all_ordena_por_id_desc_y_excluye_eliminados(): void
    {
        $this->insertar('categorias', [
            ['id' => 1, 'nombre' => 'A'],
            ['id' => 2, 'nombre' => 'B'],
            ['id' => 3, 'nombre' => 'C', 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame([2, 1], $this->repo->all()->pluck('id')->all());
    }

    public function test_all_incluye_eliminados(): void
    {
        $this->insertar('categorias', [
            ['id' => 1, 'nombre' => 'A'],
            ['id' => 2, 'nombre' => 'B', 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $ids = $this->repo->all(['incluir_eliminados' => true])->pluck('id')->all();

        $this->assertSame([2, 1], $ids);
    }

    public function test_all_solo_eliminados(): void
    {
        $this->insertar('categorias', [
            ['id' => 1, 'nombre' => 'A'],
            ['id' => 2, 'nombre' => 'B', 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame(
            [2],
            $this->repo->all(['solo_eliminados' => true])->pluck('id')->all()
        );
    }

    public function test_find_by_id_y_find_deleted_by_id(): void
    {
        $this->insertar('categorias', [
            ['id' => 1, 'nombre' => 'Activa'],
            ['id' => 2, 'nombre' => 'Borrada', 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame('Activa', $this->repo->findById(1)?->nombre);
        $this->assertNull($this->repo->findById(2));
        $this->assertNull($this->repo->findById(99));

        $this->assertSame('Borrada', $this->repo->findDeletedById(2)?->nombre);
        $this->assertNull($this->repo->findDeletedById(1));
    }

    public function test_exists_by_name_respeta_except_id(): void
    {
        $this->insertar('categorias', [
            ['id' => 1, 'nombre' => 'Playa'],
            ['id' => 2, 'nombre' => 'Montaña'],
        ]);

        $this->assertTrue($this->repo->existsByName('Playa'));
        $this->assertFalse($this->repo->existsByName('Ciudad'));
        $this->assertFalse($this->repo->existsByName('Playa', 1));
        $this->assertTrue($this->repo->existsByName('Playa', 2));
    }

    public function test_has_properties(): void
    {
        $this->insertar('categorias', [
            ['id' => 1, 'nombre' => 'Con props'],
            ['id' => 2, 'nombre' => 'Sin props'],
        ]);
        $this->insertar('propiedades', [
            ['id' => 10, 'categoria_id' => 1],
        ]);

        $conProps = $this->repo->findById(1);
        $sinProps = $this->repo->findById(2);

        $this->assertTrue($this->repo->hasProperties($conProps));
        $this->assertFalse($this->repo->hasProperties($sinProps));
    }

    public function test_create_update_delete_y_restore(): void
    {
        $categoria = $this->repo->create(['nombre' => 'Nueva']);

        $this->assertNotNull($categoria->id);
        $this->assertSame('Nueva', $categoria->fresh()->nombre);

        $this->assertTrue($this->repo->update($categoria, ['nombre' => 'Editada']));
        $this->assertSame('Editada', $categoria->fresh()->nombre);

        $this->assertTrue($this->repo->delete($categoria));
        $this->assertNull($this->repo->findById($categoria->id));
        $this->assertNotNull($this->repo->findDeletedById($categoria->id));

        $borrada = $this->repo->findDeletedById($categoria->id);
        $this->assertTrue($this->repo->restore($borrada));
        $this->assertNotNull($this->repo->findById($categoria->id));
    }
}
