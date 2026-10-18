<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentUsuarioRepository;

final class EloquentUsuarioRepositoryTest extends RepositorioTestCase
{
    private EloquentUsuarioRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentUsuarioRepository();
    }

    private function sembrar(): void
    {
        $this->insertar('roles', [['id' => 1, 'nombre' => 'usuario']]);
        $this->insertar('usuarios', [
            ['id' => 1, 'nombre' => 'Ana', 'email' => 'a@x.test', 'rol_id' => 1],
            ['id' => 2, 'nombre' => 'Beto', 'email' => 'b@x.test', 'rol_id' => 1, 'deleted_at' => '2026-01-01 00:00:00'],
        ]);
    }

    public function test_all_con_filtros_de_eliminados(): void
    {
        $this->sembrar();

        $this->assertSame([1], $this->repo->all()->pluck('id')->all());
        $this->assertSame(
            [2],
            $this->repo->all(['solo_eliminados' => true])->pluck('id')->all()
        );
        $this->assertSame(
            [2, 1],
            $this->repo->all(['incluir_eliminados' => true])->pluck('id')->all()
        );
    }

    public function test_find_by_id_y_deleted(): void
    {
        $this->sembrar();

        $this->assertNotNull($this->repo->findById(1));
        $this->assertNull($this->repo->findById(2));
        $this->assertNull($this->repo->findById(99));
        $this->assertNotNull($this->repo->findDeletedById(2));
        $this->assertNull($this->repo->findDeletedById(1));
    }

    public function test_find_by_id_with_role(): void
    {
        $this->sembrar();

        $usuario = $this->repo->findByIdWithRole(1);

        $this->assertTrue($usuario->relationLoaded('rol'));
        $this->assertSame(1, $usuario->rol->id);
    }

    public function test_find_by_email_y_exists_by_email(): void
    {
        $this->sembrar();

        $this->assertSame(1, $this->repo->findByEmail('a@x.test')->id);
        $this->assertNull($this->repo->findByEmail('nadie@x.test'));

        $this->assertTrue($this->repo->existsByEmail('a@x.test'));
        $this->assertFalse($this->repo->existsByEmail('a@x.test', 1));
        $this->assertFalse($this->repo->existsByEmail('nadie@x.test'));
    }

    public function test_create_y_create_with_role(): void
    {
        $this->sembrar();

        $usuario = $this->repo->create([
            'nombre' => 'Nuevo',
            'email' => 'n@x.test',
        ]);
        $this->assertNotNull($usuario->id);

        $conRol = $this->repo->createWithRole(
            ['nombre' => 'Con Rol', 'email' => 'r@x.test'],
            1
        );
        $this->assertSame(1, $conRol->rol_id);
        $this->assertSame(1, $conRol->fresh()->rol_id);
    }

    public function test_update_delete_y_restore(): void
    {
        $this->sembrar();

        $usuario = $this->repo->findById(1);

        $this->assertTrue($this->repo->update($usuario, ['nombre' => 'Ana Editada']));
        $this->assertSame('Ana Editada', $usuario->fresh()->nombre);

        $this->assertTrue($this->repo->delete($usuario));
        $this->assertNull($this->repo->findById(1));

        $this->assertTrue(
            $this->repo->restore($this->repo->findDeletedById(1))
        );
        $this->assertNotNull($this->repo->findById(1));
    }
}
