<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentResenaRepository;

final class EloquentResenaRepositoryTest extends RepositorioTestCase
{
    private EloquentResenaRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentResenaRepository();
    }

    private function sembrar(): void
    {
        $this->insertar('usuarios', [
            ['id' => 1, 'nombre' => 'Prop', 'apellido' => 'Uno'],
            ['id' => 2, 'nombre' => 'Inq', 'apellido' => 'Dos'],
            ['id' => 3, 'nombre' => 'Otro', 'apellido' => 'Tres'],
        ]);
        $this->insertar('propiedades', [
            ['id' => 10, 'usuario_id' => 1],
            ['id' => 20, 'usuario_id' => 1],
        ]);
        $this->insertar('reservas', [
            ['id' => 100, 'propiedad_id' => 10, 'usuario_id' => 2],
            ['id' => 200, 'propiedad_id' => 20, 'usuario_id' => 3],
        ]);
        $this->insertar('resenas', [
            ['id' => 1, 'reserva_id' => 100, 'tipo' => 'propiedad', 'calificador_id' => 2, 'calificacion' => 5, 'fecha_publicacion' => '2026-01-01 10:00:00'],
            ['id' => 2, 'reserva_id' => 100, 'tipo' => 'inquilino', 'calificador_id' => 1, 'calificacion' => 3, 'fecha_publicacion' => '2026-02-01 10:00:00'],
            ['id' => 3, 'reserva_id' => 200, 'tipo' => 'propiedad', 'calificador_id' => 3, 'calificacion' => 4, 'fecha_publicacion' => '2026-03-01 10:00:00', 'deleted_at' => '2026-03-02 10:00:00'],
        ]);
    }

    public function test_all_ordena_y_filtra_eliminados(): void
    {
        $this->sembrar();

        $this->assertSame([2, 1], $this->repo->all()->pluck('id')->all());
        $this->assertSame([3, 2, 1], $this->repo->all(['incluir_eliminados' => true])->pluck('id')->all());
        $this->assertSame([3], $this->repo->all(['solo_eliminados' => true])->pluck('id')->all());
    }

    public function test_all_filtros_simples(): void
    {
        $this->sembrar();

        $this->assertSame([1], $this->repo->all(['tipo' => 'propiedad'])->pluck('id')->all());
        $this->assertSame([2], $this->repo->all(['calificacion' => 3])->pluck('id')->all());
        $this->assertSame([1], $this->repo->all(['calificacion_min' => 4])->pluck('id')->all());
        $this->assertSame([2], $this->repo->all(['calificacion_max' => 3])->pluck('id')->all());
        $this->assertSame([2, 1], $this->repo->all(['reserva_id' => 100])->pluck('id')->all());
        $this->assertSame([1], $this->repo->all(['calificador_id' => 2])->pluck('id')->all());
    }

    public function test_all_filtra_por_reserva(): void
    {
        $this->sembrar();

        $this->assertSame([2, 1], $this->repo->all(['propiedad_id' => 10])->pluck('id')->all());
        $this->assertSame([], $this->repo->all(['propiedad_id' => 20])->pluck('id')->all());
        $this->assertSame([3], $this->repo->all(['propiedad_id' => 20, 'incluir_eliminados' => true])->pluck('id')->all());
        $this->assertSame([2, 1], $this->repo->all(['usuario_id' => 2])->pluck('id')->all());
    }

    public function test_all_filtra_por_fechas(): void
    {
        $this->sembrar();

        $this->assertSame([2], $this->repo->all(['fecha_desde' => '2026-02-01 00:00:00'])->pluck('id')->all());
        $this->assertSame([1], $this->repo->all(['fecha_hasta' => '2026-01-15 00:00:00'])->pluck('id')->all());
    }

    public function test_find_by_id_y_find_deleted(): void
    {
        $this->sembrar();

        $this->assertNotNull($this->repo->findById(1));
        $this->assertNull($this->repo->findById(3));
        $this->assertNotNull($this->repo->findDeletedById(3));
        $this->assertNull($this->repo->findDeletedById(1));
    }

    public function test_create_update_delete_y_restore(): void
    {
        $this->sembrar();

        $resena = $this->repo->create([
            'reserva_id' => 100,
            'tipo' => 'propiedad',
            'calificador_id' => 2,
            'calificacion' => 2,
            'fecha_publicacion' => '2026-05-01 10:00:00',
        ]);
        $this->assertNotNull($resena->id);

        $this->assertTrue($this->repo->update($resena, ['calificacion' => 1]));
        $this->assertSame(1, $resena->fresh()->calificacion);

        $this->assertTrue($this->repo->delete($resena));
        $this->assertNull($this->repo->findById($resena->id));

        $this->assertTrue($this->repo->restore($resena));
        $this->assertNotNull($this->repo->findById($resena->id));
    }

    public function test_consultas_por_reserva_propiedad_y_usuario(): void
    {
        $this->sembrar();

        $this->assertSame([2, 1], $this->repo->getByReserva(100)->pluck('id')->all());
        $this->assertSame([1], $this->repo->getByPropiedad(10)->pluck('id')->all());
        $this->assertSame([2], $this->repo->getByUsuario(2)->pluck('id')->all());
        $this->assertSame([1], $this->repo->getByCalificador(2)->pluck('id')->all());
        $this->assertSame([1], $this->repo->getByPropietario(1)->pluck('id')->all());
        $this->assertSame([1], $this->repo->getByCalificadorYTipo(2, 'propiedad')->pluck('id')->all());
    }

    public function test_promedios(): void
    {
        $this->sembrar();

        $this->assertSame(5.0, $this->repo->getPromedioByPropiedad(10));
        $this->assertSame(0.0, $this->repo->getPromedioByPropiedad(20));
        $this->assertSame(5.0, $this->repo->getPromedioByPropietario(1));
        $this->assertSame(3.0, $this->repo->getPromedioByUsuario(2));
        $this->assertSame(0.0, $this->repo->getPromedioByUsuario(99));
    }

    public function test_existe_por_reserva_y_tipo_incluye_eliminadas(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->existePorReservaYTipo(200, 'propiedad'));
        $this->assertTrue($this->repo->existePorReservaYTipo(100, 'propiedad'));
        $this->assertTrue($this->repo->existePorReservaYTipo(100, 'inquilino'));
        $this->assertFalse($this->repo->existePorReservaYTipo(999, 'propiedad'));
    }
}
