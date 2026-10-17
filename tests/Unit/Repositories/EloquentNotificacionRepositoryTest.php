<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentNotificacionRepository;

final class EloquentNotificacionRepositoryTest extends RepositorioTestCase
{
    private EloquentNotificacionRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentNotificacionRepository();
    }

    private function sembrar(): void
    {
        $this->insertar('notificaciones', [
            ['id' => 1, 'usuario_id' => 1, 'tipo' => 'reserva', 'referencia_id' => 50, 'leida' => 0, 'fecha_notificacion' => '2026-01-01 10:00:00'],
            ['id' => 2, 'usuario_id' => 1, 'tipo' => 'sistema', 'referencia_id' => null, 'leida' => 1, 'fecha_notificacion' => '2026-02-01 10:00:00'],
            ['id' => 3, 'usuario_id' => 2, 'tipo' => 'reserva', 'referencia_id' => 60, 'leida' => 0, 'fecha_notificacion' => '2026-03-01 10:00:00'],
            ['id' => 4, 'usuario_id' => 1, 'tipo' => 'reserva', 'referencia_id' => 70, 'leida' => 0, 'fecha_notificacion' => '2026-04-01 10:00:00', 'deleted_at' => '2026-04-02 10:00:00'],
        ]);
    }

    public function test_get_by_usuario_excluye_eliminadas_y_ordena_desc(): void
    {
        $this->sembrar();

        $notificaciones = $this->repo->getByUsuario(1);

        $this->assertSame([2, 1], array_column($notificaciones, 'id'));
    }

    public function test_contar_no_leidas(): void
    {
        $this->sembrar();

        $this->assertSame(1, $this->repo->contarNoLeidas(1));
        $this->assertSame(1, $this->repo->contarNoLeidas(2));
        $this->assertSame(0, $this->repo->contarNoLeidas(99));
    }

    public function test_find_by_id_incluye_eliminadas(): void
    {
        $this->sembrar();

        $this->assertNotNull($this->repo->findById(4));
        $this->assertNotNull($this->repo->findById(1));
        $this->assertNull($this->repo->findById(99));
    }

    public function test_create(): void
    {
        $notificacion = $this->repo->create([
            'usuario_id' => 1,
            'tipo' => 'sistema',
            'titulo' => 'Hola',
            'mensaje' => 'Mundo',
            'leida' => 0,
            'fecha_notificacion' => '2026-05-01 10:00:00',
        ]);

        $this->assertNotNull($notificacion->id);
        $this->assertSame('Hola', $notificacion->fresh()->titulo);
    }

    public function test_update(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->update(1, ['leida' => 1]));
        $this->assertTrue($this->repo->findById(1)->leida);
        $this->assertFalse($this->repo->update(99, ['leida' => 1]));
    }

    public function test_marcar_todas_leidas_solo_afecta_al_usuario(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->marcarTodasLeidas(1));
        $this->assertSame(0, $this->repo->contarNoLeidas(1));
        $this->assertSame(1, $this->repo->contarNoLeidas(2));

        $this->assertTrue($this->repo->marcarTodasLeidas(2));
        $this->assertSame(0, $this->repo->contarNoLeidas(2));

        $this->assertFalse($this->repo->marcarTodasLeidas(999));
    }

    public function test_find_no_leida_por_usuario_y_referencia(): void
    {
        $this->sembrar();

        $this->assertNotNull(
            $this->repo->findNoLeidaPorUsuarioYReferencia(1, 'reserva', 50)
        );
        $this->assertNull(
            $this->repo->findNoLeidaPorUsuarioYReferencia(1, 'reserva', 51)
        );
        $this->assertNull(
            $this->repo->findNoLeidaPorUsuarioYReferencia(1, 'sistema', 999)
        );
        $this->assertNotNull(
            $this->repo->findNoLeidaPorUsuarioYReferencia(2, 'reserva', 60)
        );
    }

    public function test_marcar_todas_leidas_no_afecta_otras_cuentas(): void
    {
        $this->sembrar();

        $this->repo->marcarTodasLeidas(1);

        $this->assertSame(1, $this->repo->contarNoLeidas(2));
    }
}
