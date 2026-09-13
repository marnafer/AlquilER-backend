<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PropiedadServicioService;
use App\Repositories\PropiedadServicioRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\ServicioRepositoryInterface;
use App\Services\LogActividadService;
use App\Models\Propiedad;
use App\Models\Servicio;

class PropiedadServicioServiceTest extends TestCase
{
    private $propiedadServicioRepository;
    private $propiedadRepository;
    private $servicioRepository;
    private $logService;
    private $propiedadServicioService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propiedadServicioRepository = $this->createMock(PropiedadServicioRepositoryInterface::class);
        $this->propiedadRepository = $this->createMock(PropiedadRepositoryInterface::class);
        $this->servicioRepository = $this->createMock(ServicioRepositoryInterface::class);
        $this->logService = $this->createMock(LogActividadService::class);

        $this->propiedadServicioService = new PropiedadServicioService(
            $this->propiedadServicioRepository,
            $this->propiedadRepository,
            $this->servicioRepository,
            $this->logService
        );
    }

    /** @test */
    public function test_it_can_get_servicios_by_propiedad()
    {
        $expected = [['id' => 1, 'servicio_id' => 1]];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(new Propiedad(['id' => 1]));

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('getByPropiedad')
            ->with(1)
            ->willReturn($expected);

        $result = $this->propiedadServicioService->obtenerServiciosPorPropiedad(1);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function test_it_can_asignar_servicio()
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(new Propiedad(['id' => 1]));

        $this->servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn(new Servicio(['id' => 2])); // Corregido a instancia de Servicio

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('attach')
            ->with(1, 2)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar');

        $result = $this->propiedadServicioService->asignarServicio(1, 2, 3);
        $this->assertTrue($result);
    }

    /** @test */
    public function test_it_throws_exception_when_property_not_found_for_asignar()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("La propiedad no existe");
        $this->expectExceptionCode(404);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->propiedadServicioService->asignarServicio(999, 1, 1);
    }

    /** @test */
    public function test_it_throws_exception_when_servicio_not_found()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("El servicio no existe");
        $this->expectExceptionCode(404);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(new Propiedad(['id' => 1]));

        $this->servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->propiedadServicioService->asignarServicio(1, 999, 1);
    }

    /** @test */
    public function test_it_can_desasignar_servicio()
    {
        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(true);

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('detach')
            ->with(1, 2)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar');

        $result = $this->propiedadServicioService->desasignarServicio(1, 2, 3);
        $this->assertTrue($result);
    }

    /** @test */
    public function test_it_throws_exception_when_desasignar_servicio_not_found()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("La propiedad no tiene este servicio asignado");
        $this->expectExceptionCode(404);

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 999)
            ->willReturn(false);

        $this->propiedadServicioService->desasignarServicio(1, 999, 3);
    }

    /** @test */
    public function test_it_can_sync_servicios()
    {
        $servicioIds = [1, 2, 3];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(new Propiedad(['id' => 1]));

        $this->servicioRepository
            ->expects($this->exactly(3))
            ->method('findById')
            ->willReturn(new Servicio(['id' => 1])); // Corregido a instancia de Servicio

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('sync')
            ->with(1, $servicioIds)
            ->willReturn([
                'agregados' => [1, 2],
                'eliminados' => [3],
                'mantenidos' => []
            ]);

        $this->logService
            ->expects($this->once())
            ->method('registrar');

        $result = $this->propiedadServicioService->sincronizarServicios(1, $servicioIds, 3);

        $this->assertArrayHasKey('agregados', $result);
        $this->assertArrayHasKey('eliminados', $result);
    }
}