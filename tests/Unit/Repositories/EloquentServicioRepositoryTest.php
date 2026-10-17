<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Servicio;
use App\Repositories\EloquentServicioRepository;

final class EloquentServicioRepositoryTest extends RepositorioTestCase
{
    private EloquentServicioRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentServicioRepository();
    }

    public function test_all_ordena_por_id_asc_y_excluye_eliminados(): void
    {
        $this->insertar('servicios', [
            ['id' => 1, 'nombre' => 'Luz'],
            ['id' => 2, 'nombre' => 'Agua'],
            ['id' => 3, 'nombre' => 'Gas', 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame([1, 2], $this->repo->all()->pluck('id')->all());
        $this->assertSame(
            [3],
            $this->repo->all(['solo_eliminados' => true])->pluck('id')->all()
        );
        $this->assertSame(
            [1, 2, 3],
            $this->repo->all(['incluir_eliminados' => true])->pluck('id')->all()
        );
    }

    public function test_find_by_id_y_find_deleted_by_id(): void
    {
        $this->insertar('servicios', [
            ['id' => 1, 'nombre' => 'Luz'],
            ['id' => 2, 'nombre' => 'Gas', 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame('Luz', $this->repo->findById(1)?->nombre);
        $this->assertNull($this->repo->findById(2));
        $this->assertSame('Gas', $this->repo->findDeletedById(2)?->nombre);
        $this->assertNull($this->repo->findDeletedById(1));
    }

    public function test_find_by_ids(): void
    {
        $this->insertar('servicios', [
            ['id' => 1, 'nombre' => 'Luz'],
            ['id' => 2, 'nombre' => 'Agua'],
            ['id' => 3, 'nombre' => 'Gas'],
        ]);

        $this->assertSame(
            [1, 3],
            $this->repo->findByIds([1, 3, 99])->pluck('id')->sort()->values()->all()
        );
        $this->assertTrue($this->repo->findByIds([99])->isEmpty());
    }

    public function test_exists_by_name_respeta_except_id(): void
    {
        $this->insertar('servicios', [
            ['id' => 1, 'nombre' => 'Luz'],
        ]);

        $this->assertTrue($this->repo->existsByName('Luz'));
        $this->assertFalse($this->repo->existsByName('Agua'));
        $this->assertFalse($this->repo->existsByName('Luz', 1));
    }

    public function test_has_properties_a_traves_del_pivote(): void
    {
        $this->insertar('servicios', [
            ['id' => 1, 'nombre' => 'Con props'],
            ['id' => 2, 'nombre' => 'Sin props'],
        ]);
        $this->insertar('propiedades', [
            ['id' => 10],
        ]);
        $this->insertar('propiedad_servicio', [
            ['propiedad_id' => 10, 'servicio_id' => 1],
        ]);

        $this->assertTrue($this->repo->hasProperties(Servicio::find(1)));
        $this->assertFalse($this->repo->hasProperties(Servicio::find(2)));
    }

    public function test_create_update_delete_y_restore(): void
    {
        $servicio = $this->repo->create(['nombre' => 'Nuevo']);

        $this->assertSame('Nuevo', $servicio->fresh()->nombre);
        $this->assertTrue($this->repo->update($servicio, ['nombre' => 'Editado']));
        $this->assertSame('Editado', $servicio->fresh()->nombre);

        $this->assertTrue($this->repo->delete($servicio));
        $this->assertNull($this->repo->findById($servicio->id));

        $this->assertTrue(
            $this->repo->restore($this->repo->findDeletedById($servicio->id))
        );
        $this->assertNotNull($this->repo->findById($servicio->id));
    }
}
