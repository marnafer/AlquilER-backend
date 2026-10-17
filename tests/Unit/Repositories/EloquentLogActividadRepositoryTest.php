<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentLogActividadRepository;

final class EloquentLogActividadRepositoryTest extends RepositorioTestCase
{
    private EloquentLogActividadRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentLogActividadRepository();
    }

    public function test_all_ordena_por_fecha_desc_y_resuelve_el_usuario(): void
    {
        $this->insertar('usuarios', [
            ['id' => 1, 'nombre' => 'Ana', 'apellido' => 'Perez', 'email' => 'ana@x.test'],
        ]);
        $this->insertar('logs_actividad', [
            ['id' => 1, 'usuario_id' => 1, 'accion' => 'login', 'ip_address' => '10.0.0.1', 'fecha' => '2026-01-01 10:00:00'],
            ['id' => 2, 'usuario_id' => null, 'accion' => 'logout', 'ip_address' => '10.0.0.2', 'fecha' => '2026-02-01 10:00:00'],
        ]);

        $logs = $this->repo->all();

        $this->assertSame([2, 1], $logs->pluck('id')->all());

        $conUsuario = $logs->firstWhere('id', 1);
        $this->assertSame('Ana Perez', $conUsuario['usuario_nombre']);
        $this->assertSame('ana@x.test', $conUsuario['usuario_email']);

        $sinUsuario = $logs->firstWhere('id', 2);
        $this->assertNull($sinUsuario['usuario_nombre']);
        $this->assertNull($sinUsuario['usuario_email']);
    }

    public function test_find_by_id_devuelve_el_array_transformado(): void
    {
        $this->insertar('usuarios', [
            ['id' => 1, 'nombre' => 'Ana', 'apellido' => 'Perez', 'email' => 'ana@x.test'],
        ]);
        $this->insertar('logs_actividad', [
            ['id' => 5, 'usuario_id' => 1, 'accion' => 'editar', 'ip_address' => '1.2.3.4'],
        ]);

        $log = $this->repo->findById(5);

        $this->assertSame('editar', $log['accion']);
        $this->assertSame('Ana Perez', $log['usuario_nombre']);
        $this->assertNull($this->repo->findById(99));
    }

    public function test_create_persiste_el_log(): void
    {
        $log = $this->repo->create([
            'usuario_id' => 1,
            'accion' => 'crear',
            'ip_address' => '9.9.9.9',
        ]);

        $this->assertNotNull($log->id);
        $this->assertSame('crear', $log->accion);
        $this->assertTrue(
            $this->capsule
                ->table('logs_actividad')
                ->where('id', $log->id)
                ->exists()
        );
    }
}
