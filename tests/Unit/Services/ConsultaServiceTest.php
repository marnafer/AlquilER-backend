<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ConsultaService;
use App\Repositories\ConsultaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\LogActividadService;
use App\Models\Propiedad;

class ConsultaServiceTest extends TestCase
{
    private $consultaRepository;
    private $propiedadRepository;
    private $usuarioRepository;
    private $logService;
    private $consultaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consultaRepository = $this->createMock(ConsultaRepositoryInterface::class);
        $this->propiedadRepository = $this->createMock(PropiedadRepositoryInterface::class);
        $this->usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $this->logService = $this->createMock(LogActividadService::class);

        $this->consultaService = new ConsultaService(
            $this->consultaRepository,
            $this->propiedadRepository,
            $this->usuarioRepository,
            $this->logService
        );
    }

    /** @test */
    public function test_it_can_list_consultas()
    {
        $filtros = ['usuario_id' => 1];
        $expected = [['id' => 1, 'propiedad_id' => 1]];

        $this->consultaRepository
            ->expects($this->once())
            ->method('getAll')
            ->with($filtros)
            ->willReturn($expected);

        $result = $this->consultaService->listarConsultas($filtros);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function test_it_can_create_consulta()
    {
        $propiedadMock = new Propiedad(['id' => 1]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedadMock);

        $this->consultaRepository
            ->expects($this->once())
            ->method('create')
            ->willReturn(1);

        $this->logService
            ->expects($this->once())
            ->method('registrar');

        $result = $this->consultaService->crearConsulta([
            'propiedad_id' => 1,
            'usuario_id' => 2
        ]);

        $this->assertEquals(1, $result);
    }

    /** @test */
    public function test_it_throws_exception_when_property_not_found()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("La propiedad no existe");
        $this->expectExceptionCode(404);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->consultaService->crearConsulta([
            'propiedad_id' => 999,
            'usuario_id' => 2
        ]);
    }

    /** @test */
    public function test_it_can_get_consultas_by_usuario()
    {
        $expected = [['id' => 1]];

        $this->consultaRepository
            ->expects($this->once())
            ->method('getByUsuario')
            ->with(1)
            ->willReturn($expected);

        $result = $this->consultaService->obtenerConsultasPorUsuario(1, 1, 1);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function test_it_throws_exception_when_not_authorized_to_view_usuario_consultas()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("No autorizado");
        $this->expectExceptionCode(403);

        $this->consultaService->obtenerConsultasPorUsuario(1, 2, 1);
    }

    /** @test */
    public function test_it_can_get_consultas_by_propiedad()
    {
        $expected = [['id' => 1]];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(new Propiedad(['id' => 1, 'usuario_id' => 1]));

        $this->consultaRepository
            ->expects($this->once())
            ->method('getByPropiedad')
            ->with(1)
            ->willReturn($expected);

        $result = $this->consultaService->obtenerConsultasPorPropiedad(1, 1, 1);
        $this->assertEquals($expected, $result);
    }
}