<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentMensajeConsultaRepository;

final class EloquentMensajeConsultaRepositoryTest extends RepositorioTestCase
{
    private EloquentMensajeConsultaRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentMensajeConsultaRepository();
    }

    private function sembrar(): void
    {
        $this->insertar('usuarios', [
            ['id' => 1, 'nombre' => 'A', 'apellido' => 'Uno'],
            ['id' => 2, 'nombre' => 'B', 'apellido' => 'Dos'],
        ]);
        $this->insertar('mensajes_consultas', [
            ['id' => 1, 'consulta_id' => 1, 'usuario_id' => 1, 'mensaje' => 'm1', 'fecha_mensaje' => '2026-01-01 09:00:00'],
            ['id' => 2, 'consulta_id' => 1, 'usuario_id' => 2, 'mensaje' => 'm2', 'fecha_mensaje' => '2026-01-01 10:00:00'],
            ['id' => 3, 'consulta_id' => 1, 'usuario_id' => 1, 'mensaje' => 'm3', 'fecha_mensaje' => '2026-01-01 11:00:00'],
            ['id' => 4, 'consulta_id' => 2, 'usuario_id' => 1, 'mensaje' => 'otro', 'fecha_mensaje' => '2026-01-01 12:00:00'],
        ]);
    }

    public function test_find_by_consulta_id_ordena_ascendente_y_carga_usuario(): void
    {
        $this->sembrar();

        $mensajes = $this->repo->findByConsultaId(1);

        $this->assertSame([1, 2, 3], $mensajes->pluck('id')->all());
        $this->assertTrue($mensajes->first()->relationLoaded('usuario'));
    }

    public function test_find_latest_by_consulta_id(): void
    {
        $this->sembrar();

        $this->assertSame(
            [3, 2],
            $this->repo->findLatestByConsultaId(1, 2)->pluck('id')->all()
        );
    }

    public function test_find_older_by_consulta_id(): void
    {
        $this->sembrar();

        $this->assertSame(
            [2, 1],
            $this->repo->findOlderByConsultaId(1, 3, 2)->pluck('id')->all()
        );
    }

    public function test_find_newer_by_consulta_id(): void
    {
        $this->sembrar();

        $this->assertSame(
            [2, 3],
            $this->repo->findNewerByConsultaId(1, 1, 10)->pluck('id')->all()
        );
    }

    public function test_find_by_id(): void
    {
        $this->sembrar();

        $this->assertSame('m1', $this->repo->findById(1)?->mensaje);
        $this->assertNull($this->repo->findById(99));
    }

    public function test_create(): void
    {
        $mensaje = $this->repo->create([
            'consulta_id' => 1,
            'usuario_id' => 1,
            'mensaje' => 'nuevo',
            'fecha_mensaje' => '2026-05-01 08:00:00',
        ]);

        $this->assertNotNull($mensaje->id);
        $this->assertSame('nuevo', $mensaje->fresh()->mensaje);
    }

    public function test_update(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->update(1, ['mensaje' => 'editado']));
        $this->assertSame('editado', $this->repo->findById(1)->mensaje);
        $this->assertFalse($this->repo->update(99, ['mensaje' => 'x']));
    }

    public function test_delete_es_soft_delete(): void
    {
        $this->sembrar();

        $this->repo->delete(3);

        $this->assertNull($this->repo->findById(3));
        $this->assertNotNull($this->repo->findById(2));
    }
}
