<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentConsultaRepository;

final class EloquentConsultaRepositoryTest extends RepositorioTestCase
{
    private EloquentConsultaRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentConsultaRepository();
    }

    private function sembrar(): void
    {
        $this->insertar('usuarios', [
            ['id' => 3],
            ['id' => 4],
            ['id' => 5],
        ]);
        $this->insertar('propiedades', [
            ['id' => 20, 'usuario_id' => 4],
            ['id' => 21, 'usuario_id' => 3],
        ]);
        $this->insertar('consultas', [
            ['id' => 10, 'propiedad_id' => 20, 'usuario_id' => 3, 'fecha_consulta' => '2026-10-01 10:00:00'],
            ['id' => 11, 'propiedad_id' => 20, 'usuario_id' => 4, 'fecha_consulta' => '2026-10-05 10:00:00'],
            ['id' => 12, 'propiedad_id' => 21, 'usuario_id' => 3, 'fecha_consulta' => '2026-10-03 10:00:00'],
            ['id' => 13, 'propiedad_id' => 20, 'usuario_id' => 3, 'fecha_consulta' => '2026-09-01 10:00:00', 'deleted_at' => '2026-09-02 10:00:00'],
        ]);
        $this->insertar('mensajes_consultas', [
            ['id' => 100, 'consulta_id' => 10, 'usuario_id' => 3, 'mensaje' => 'Actividad reciente', 'fecha_mensaje' => '2026-10-10 10:00:00'],
            ['id' => 101, 'consulta_id' => 11, 'usuario_id' => 4, 'mensaje' => 'Hola', 'fecha_mensaje' => '2026-10-02 10:00:00'],
        ]);
    }

    public function test_get_all_sin_filtros_ordena_desc_y_excluye_eliminados(): void
    {
        $this->sembrar();

        $this->assertSame(
            [11, 12, 10],
            array_column($this->repo->getAll(), 'id')
        );
    }

    public function test_get_all_filtra_por_usuario_y_propiedad(): void
    {
        $this->sembrar();

        $this->assertSame(
            [12, 10],
            array_column($this->repo->getAll(['usuario_id' => 3]), 'id')
        );
        $this->assertSame(
            [11, 10],
            array_column($this->repo->getAll(['propiedad_id' => 20]), 'id')
        );
    }

    public function test_get_all_filtra_por_rango_de_fechas(): void
    {
        $this->sembrar();

        $this->assertSame(
            [11, 12],
            array_column(
                $this->repo->getAll(['fecha_desde' => '2026-10-02']),
                'id'
            )
        );
        $this->assertSame(
            [10],
            array_column(
                $this->repo->getAll(['fecha_hasta' => '2026-10-02']),
                'id'
            )
        );
    }

    public function test_get_all_incluye_y_solo_eliminados(): void
    {
        $this->sembrar();

        $this->assertSame(
            [11, 12, 10, 13],
            array_column(
                $this->repo->getAll(['incluir_eliminados' => true]),
                'id'
            )
        );
        $this->assertSame(
            [13],
            array_column(
                $this->repo->getAll(['solo_eliminados' => true]),
                'id'
            )
        );
    }

    public function test_get_all_carga_relaciones(): void
    {
        $this->sembrar();

        $consulta = $this->repo->getAll(['propiedad_id' => 20])[0];

        $this->assertArrayHasKey('propiedad', $consulta);
        $this->assertArrayHasKey('usuario', $consulta);
    }

    public function test_find_by_id_incluye_eliminados(): void
    {
        $this->sembrar();

        $consulta = $this->repo->findById(10);

        $this->assertNotNull($consulta);
        $this->assertTrue($consulta->relationLoaded('propiedad'));
        $this->assertTrue($consulta->relationLoaded('usuario'));

        $this->assertNotNull($this->repo->findById(13));
        $this->assertNull($this->repo->findById(999));
    }

    public function test_create_devuelve_el_id(): void
    {
        $this->sembrar();

        $id = $this->repo->create([
            'propiedad_id' => 20,
            'usuario_id' => 5,
            'fecha_consulta' => '2026-11-01 10:00:00',
        ]);

        $this->assertSame(14, $id);
        $this->assertNotNull($this->repo->findById(14));
    }

    public function test_update_existente_y_ausente(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->update(10, ['usuario_id' => 5]));
        $this->assertSame(5, $this->repo->findById(10)->usuario_id);

        $this->assertFalse($this->repo->update(999, ['usuario_id' => 5]));
    }

    public function test_delete_soft_y_ausente(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->delete(10));

        $consulta = $this->repo->findById(10);
        $this->assertNotNull($consulta);
        $this->assertTrue($consulta->trashed());
        $this->assertNotContains(10, array_column($this->repo->getAll(), 'id'));

        $this->assertFalse($this->repo->delete(999));
    }

    public function test_restore_y_ausente(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->restore(13));
        $this->assertContains(13, array_column($this->repo->getAll(), 'id'));

        $this->assertFalse($this->repo->restore(999));
    }

    public function test_get_by_usuario_ordena_por_ultima_actividad(): void
    {
        $this->sembrar();

        $consultas = $this->repo->getByUsuario(3);

        $this->assertSame([10, 12], array_column($consultas, 'id'));
        $this->assertSame('2026-10-10 10:00:00', $consultas[0]['ultima_actividad']);
        $this->assertSame('Actividad reciente', $consultas[0]['ultimo_mensaje']);
    }

    public function test_get_by_propiedad_ordena_por_ultima_actividad(): void
    {
        $this->sembrar();

        $consultas = $this->repo->getByPropiedad(20);

        $this->assertSame([10, 11], array_column($consultas, 'id'));
        $this->assertSame('2026-10-10 10:00:00', $consultas[0]['ultima_actividad']);
    }

    public function test_find_by_propiedad_y_usuario_devuelve_la_mas_reciente(): void
    {
        $this->sembrar();

        $consulta = $this->repo->findByPropiedadYUsuario(21, 3);

        $this->assertNotNull($consulta);
        $this->assertSame(12, $consulta->id);

        $this->assertNull($this->repo->findByPropiedadYUsuario(20, 99));
    }

    public function test_get_recibidas_por_propietario(): void
    {
        $this->sembrar();

        $this->assertSame(
            [10, 11],
            array_column($this->repo->getRecibidasPorPropietario(4), 'id')
        );
        $this->assertSame(
            [12],
            array_column($this->repo->getRecibidasPorPropietario(3), 'id')
        );
    }

    public function test_existe_consulta_entre_usuarios_en_ambos_sentidos(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->existeConsultaEntreUsuarios(3, 4));
        $this->assertTrue($this->repo->existeConsultaEntreUsuarios(4, 3));
        $this->assertFalse($this->repo->existeConsultaEntreUsuarios(3, 5));
    }
}
