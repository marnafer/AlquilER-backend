<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Repositories\EloquentPropiedadServicioRepository;

final class EloquentPropiedadServicioRepositoryTest extends RepositorioTestCase
{
    private EloquentPropiedadServicioRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new EloquentPropiedadServicioRepository();
    }

    private function sembrar(): void
    {
        $this->insertar('servicios', [
            ['id' => 1, 'nombre' => 'Luz'],
            ['id' => 2, 'nombre' => 'Agua'],
            ['id' => 3, 'nombre' => 'Gas'],
        ]);
        $this->insertar('propiedades', [
            ['id' => 10, 'titulo' => 'Casa'],
            ['id' => 20, 'titulo' => 'Depto'],
        ]);
        $this->insertar('propiedad_servicio', [
            ['propiedad_id' => 10, 'servicio_id' => 1],
            ['propiedad_id' => 10, 'servicio_id' => 2],
            ['propiedad_id' => 20, 'servicio_id' => 3],
        ]);
    }

    public function test_get_by_propiedad_con_servicio(): void
    {
        $this->sembrar();

        $filas = $this->repo->getByPropiedad(10);

        $this->assertCount(2, $filas);
        $this->assertSame(
            ['Luz', 'Agua'],
            array_column(array_column($filas, 'servicio'), 'nombre')
        );
    }

    public function test_get_by_servicio_con_propiedad(): void
    {
        $this->sembrar();

        $filas = $this->repo->getByServicio(1);

        $this->assertCount(1, $filas);
        $this->assertSame(10, $filas[0]['propiedad']['id']);
    }

    public function test_exists(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->exists(10, 1));
        $this->assertFalse($this->repo->exists(10, 3));
    }

    public function test_attach_y_duplicado(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->attach(10, 3));
        $this->assertTrue($this->repo->exists(10, 3));
        $this->assertFalse($this->repo->attach(10, 3));
    }

    public function test_detach(): void
    {
        $this->sembrar();

        $this->assertTrue($this->repo->detach(10, 1));
        $this->assertFalse($this->repo->exists(10, 1));
        $this->assertFalse($this->repo->detach(10, 1));
    }

    public function test_attach_multiple_clasifica(): void
    {
        $this->sembrar();

        $resultado = $this->repo->attachMultiple(10, [3, 4, 3]);

        $this->assertSame([3, 4], $resultado['asignados']);
        $this->assertSame([3], $resultado['duplicados']);
        $this->assertSame([], $resultado['errores']);
    }

    public function test_sync_calcula_agregados_eliminados_y_mantenidos(): void
    {
        $this->sembrar();

        $resultado = $this->repo->sync(10, [2, 5]);

        $this->assertSame([5], $resultado['agregados']);
        $this->assertSame([1], $resultado['eliminados']);
        $this->assertSame([2], $resultado['mantenidos']);
        $this->assertSame([2, 5], $this->repo->getServicioIdsByPropiedad(10));
    }
}
