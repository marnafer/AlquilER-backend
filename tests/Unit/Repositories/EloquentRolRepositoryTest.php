<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentRolRepository;

final class EloquentRolRepositoryTest extends RepositorioTestCase
{
    private EloquentRolRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentRolRepository();
    }

    public function test_all_ordena_por_id_desc_y_excluye_eliminados(): void
    {
        $this->insertar('roles', [
            ['id' => 1, 'nombre' => 'usuario'],
            ['id' => 2, 'nombre' => 'admin'],
            ['id' => 3, 'nombre' => 'otro', 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame([2, 1], $this->repo->all()->pluck('id')->all());
        $this->assertSame(
            [3],
            $this->repo->all(['solo_eliminados' => true])->pluck('id')->all()
        );
        $this->assertSame(
            [3, 2, 1],
            $this->repo->all(['incluir_eliminados' => true])->pluck('id')->all()
        );
    }

    public function test_find_by_id_y_find_deleted_by_id(): void
    {
        $this->insertar('roles', [
            ['id' => 1, 'nombre' => 'usuario'],
            ['id' => 2, 'nombre' => 'admin', 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $this->assertSame('usuario', $this->repo->findById(1)?->nombre);
        $this->assertNull($this->repo->findById(2));
        $this->assertSame('admin', $this->repo->findDeletedById(2)?->nombre);
        $this->assertNull($this->repo->findDeletedById(1));
    }

    public function test_exists_by_name_respeta_except_id(): void
    {
        $this->insertar('roles', [
            ['id' => 1, 'nombre' => 'usuario'],
        ]);

        $this->assertTrue($this->repo->existsByName('usuario'));
        $this->assertFalse($this->repo->existsByName('admin'));
        $this->assertFalse($this->repo->existsByName('usuario', 1));
    }

    public function test_has_users(): void
    {
        $this->insertar('roles', [
            ['id' => 1, 'nombre' => 'con usuarios'],
            ['id' => 2, 'nombre' => 'sin usuarios'],
        ]);
        $this->insertar('usuarios', [
            ['id' => 7, 'rol_id' => 1],
        ]);

        $this->assertTrue($this->repo->hasUsers($this->repo->findById(1)));
        $this->assertFalse($this->repo->hasUsers($this->repo->findById(2)));
    }

    public function test_create_update_delete_y_restore(): void
    {
        $rol = $this->repo->create(['nombre' => 'nuevo']);

        $this->assertSame('nuevo', $rol->fresh()->nombre);
        $this->assertTrue($this->repo->update($rol, ['nombre' => 'editado']));
        $this->assertSame('editado', $rol->fresh()->nombre);

        $this->assertTrue($this->repo->delete($rol));
        $this->assertNull($this->repo->findById($rol->id));

        $this->assertTrue(
            $this->repo->restore($this->repo->findDeletedById($rol->id))
        );
        $this->assertNotNull($this->repo->findById($rol->id));
    }
}
