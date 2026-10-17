<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentReservaRepository;

final class EloquentReservaRepositoryTest extends RepositorioTestCase
{
    private EloquentReservaRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentReservaRepository();
    }

    private function sembrar(): void
    {
        $this->insertar('usuarios', [
            ['id' => 1, 'nombre' => 'Uno'],
            ['id' => 2, 'nombre' => 'Dos'],
            ['id' => 3, 'nombre' => 'Tres'],
        ]);
        $this->insertar('propiedades', [
            ['id' => 10, 'usuario_id' => 1],
            ['id' => 20, 'usuario_id' => 2],
            ['id' => 30, 'usuario_id' => 1],
            ['id' => 40, 'usuario_id' => 1],
        ]);
        $this->insertar('reservas', [
            ['id' => 1, 'propiedad_id' => 10, 'usuario_id' => 2, 'estado' => 'confirmada', 'fecha_reserva' => '2026-01-01 10:00:00', 'fecha_inicio_alquiler' => '2026-01-10', 'fecha_fin_alquiler' => '2026-01-20'],
            ['id' => 2, 'propiedad_id' => 10, 'usuario_id' => 2, 'estado' => 'pendiente', 'fecha_reserva' => '2026-02-01 10:00:00', 'fecha_inicio_alquiler' => '2026-03-01', 'fecha_fin_alquiler' => null],
            ['id' => 3, 'propiedad_id' => 20, 'usuario_id' => 1, 'estado' => 'confirmada', 'fecha_reserva' => '2026-03-01 10:00:00', 'fecha_inicio_alquiler' => '2026-04-01', 'fecha_fin_alquiler' => null],
            ['id' => 4, 'propiedad_id' => 10, 'usuario_id' => 2, 'estado' => 'cancelada', 'fecha_reserva' => '2025-12-01 10:00:00', 'deleted_at' => '2025-12-02 10:00:00'],
        ]);
    }

    public function test_get_all_ordena_y_filtra(): void
    {
        $this->sembrar();

        $this->assertSame([3, 2, 1], array_column($this->repo->getAll(), 'id'));
        $this->assertSame([2, 1], array_column($this->repo->getAll(['usuario_id' => 2]), 'id'));
        $this->assertSame([2, 1], array_column($this->repo->getAll(['propiedad_id' => 10]), 'id'));
        $this->assertSame([3, 1], array_column($this->repo->getAll(['estado' => 'confirmada']), 'id'));
        $this->assertSame([4], array_column($this->repo->getAll(['solo_eliminados' => true]), 'id'));
        $this->assertSame([3, 2, 1, 4], array_column($this->repo->getAll(['incluir_eliminados' => true]), 'id'));
    }

    public function test_find_by_id_con_trashed(): void
    {
        $this->sembrar();

        $this->assertNotNull($this->repo->findById(1));
        $this->assertNotNull($this->repo->findById(4));
        $this->assertNotNull($this->repo->findDeletedById(4));
        $this->assertNull($this->repo->findDeletedById(1));
        $this->assertNull($this->repo->findById(99));
    }

    public function test_create_update_delete_y_restore(): void
    {
        $this->sembrar();

        $id = $this->repo->create([
            'propiedad_id' => 10,
            'usuario_id' => 2,
            'estado' => 'pendiente',
        ]);
        $this->assertIsInt($id);
        $this->assertTrue(
            $this->capsule->table('reservas')->where('id', $id)->exists()
        );

        $this->assertTrue($this->repo->update(1, ['estado' => 'finalizada']));
        $this->assertFalse($this->repo->update(99, ['estado' => 'finalizada']));

        $this->assertTrue($this->repo->delete(1));
        $this->assertNotNull($this->repo->findDeletedById(1));
        $this->assertFalse($this->repo->delete(99));

        $this->assertTrue($this->repo->restore(1));
        $this->assertNull($this->repo->findDeletedById(1));
        $this->assertFalse($this->repo->restore(99));
    }

    public function test_get_by_usuario_y_propiedad(): void
    {
        $this->sembrar();

        $porUsuario = $this->repo->getByUsuario(2);
        $this->assertSame([2, 1], array_column($porUsuario, 'id'));
        $this->assertArrayHasKey('imagenes', $porUsuario[0]['propiedad']);

        $porPropiedad = $this->repo->getByPropiedad(10);
        $this->assertSame([2, 1], array_column($porPropiedad, 'id'));
        $this->assertArrayHasKey('usuario', $porPropiedad[0]);
    }

    public function test_listar_por_alcance_no_filtra_de_mas_ni_de_menos(): void
    {
        $this->sembrar();

        $this->assertSame([3, 2, 1], array_column($this->repo->listarPorAlcance(1, [10]), 'id'));
        $this->assertSame([2, 1], array_column($this->repo->listarPorAlcance(2, [10]), 'id'));
        $this->assertSame([2, 1], array_column($this->repo->listarPorAlcance(1, [10], ['propiedad_id' => 10]), 'id'));
        $this->assertSame([], array_column($this->repo->listarPorAlcance(2, [10], ['propiedad_id' => 20]), 'id'));
        $this->assertSame([3], array_column($this->repo->listarPorAlcance(1, [10], ['usuario_id' => 1]), 'id'));
        $this->assertSame([3, 1], array_column($this->repo->listarPorAlcance(1, [10], ['estado' => 'confirmada']), 'id'));
        $this->assertSame([3, 2, 1, 4], array_column($this->repo->listarPorAlcance(1, [10], ['incluir_eliminados' => true]), 'id'));
    }

    public function test_tiene_reserva_activa(): void
    {
        $this->sembrar();

        $this->insertar('reservas', [
            ['id' => 90, 'propiedad_id' => 40, 'usuario_id' => 1, 'estado' => 'confirmada', 'fecha_reserva' => '2019-01-01 10:00:00', 'fecha_inicio_alquiler' => '2019-01-01', 'fecha_fin_alquiler' => '2020-01-01'],
        ]);

        $this->assertTrue($this->repo->tieneReservaActiva(10));
        $this->assertFalse($this->repo->tieneReservaActiva(30));
        $this->assertFalse($this->repo->tieneReservaActiva(40));
    }

    public function test_hay_reserva_confirmada_solapada(): void
    {
        $this->sembrar();

        $this->assertFalse($this->repo->hayReservaConfirmadaSolapada(10, '2026-02-01', '2026-02-10'));
        $this->assertTrue($this->repo->hayReservaConfirmadaSolapada(10, '2026-01-15', '2026-01-25'));
        $this->assertTrue($this->repo->hayReservaConfirmadaSolapada(10, '2026-01-05', null));
        $this->assertFalse($this->repo->hayReservaConfirmadaSolapada(10, '2026-01-15', '2026-01-25', 1));
        $this->assertFalse($this->repo->hayReservaConfirmadaSolapada(30, '2026-01-01', '2026-12-31'));
    }

    public function test_finalizar_vencidas(): void
    {
        $this->sembrar();
        $this->insertar('reservas', [
            ['id' => 90, 'propiedad_id' => 40, 'usuario_id' => 1, 'estado' => 'confirmada', 'fecha_reserva' => '2019-01-01 10:00:00', 'fecha_inicio_alquiler' => '2019-01-01', 'fecha_fin_alquiler' => '2020-01-01'],
        ]);

        $this->assertSame(2, $this->repo->finalizarVencidas());

        $this->assertSame(
            'finalizada',
            $this->capsule->table('reservas')->where('id', 1)->value('estado')
        );
        $this->assertSame(
            'finalizada',
            $this->capsule->table('reservas')->where('id', 90)->value('estado')
        );
        $this->assertSame(
            'pendiente',
            $this->capsule->table('reservas')->where('id', 2)->value('estado')
        );
    }

    public function test_existe_reserva_entre_usuarios_en_ambos_sentidos(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->existeReservaEntreUsuarios(2, 1));
        $this->assertTrue($this->repo->existeReservaEntreUsuarios(1, 2));
        $this->assertFalse($this->repo->existeReservaEntreUsuarios(3, 2));
    }
}
