<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ConsultaService;
use App\Repositories\ConsultaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Repositories\MensajeConsultaRepositoryInterface;
use App\Services\LogActividadService;
use App\Models\Propiedad;
use App\Exceptions\ValidationException;
use App\Exceptions\UnauthorizedException;

class ConsultaServiceTest extends TestCase
{
    private $consultaRepository;
    private $propiedadRepository;
    private $usuarioRepository;
    private $mensajeConsultaRepository;
    private $logService;
    private $consultaService;
    private $policyMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consultaRepository = $this->createMock(ConsultaRepositoryInterface::class);
        $this->propiedadRepository = $this->createMock(PropiedadRepositoryInterface::class);
        $this->usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $this->mensajeConsultaRepository = $this->createMock(MensajeConsultaRepositoryInterface::class);
        $this->logService = $this->createMock(LogActividadService::class);
        $this->policyMock = $this->createMock(\App\Policies\ConsultaPolicy::class);

        $this->consultaService = new ConsultaService(
            $this->consultaRepository,
            $this->propiedadRepository,
            $this->usuarioRepository,
            $this->mensajeConsultaRepository,
            $this->logService,
            $this->policyMock
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

        // Verificamos que se cree el mensaje inicial en su repositorio correspondiente
        $this->mensajeConsultaRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data) {
                return $data['consulta_id'] === 1 
                    && $data['usuario_id'] === 2 
                    && $data['mensaje'] === 'Hola, me interesa la propiedad';
            }));

        $this->logService
            ->expects($this->once())
            ->method('registrar');

        $result = $this->consultaService->crearConsulta([
            'propiedad_id' => 1,
            'usuario_id' => 2,
            'mensaje' => 'Hola, me interesa la propiedad'
        ]);

        $this->assertEquals(1, $result);
    }

    /** @test */
    public function test_it_throws_exception_when_property_not_found(): void
    {
        $this->propiedadRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(ValidationException::class);
        
        $this->consultaService->crearConsulta([
            'propiedad_id' => 999,
            'usuario_id' => 1,
            'mensaje' => 'Hola'
        ]);
    }

    /** @test */
    public function test_it_can_get_consultas_by_usuario()
    {
        $expected = [['id' => 1]];

        $this->policyMock->method('puedeVerDeUsuario')->willReturn(true);

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
        $this->policyMock->method('puedeVerDeUsuario')->willReturn(false);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('No tienes permiso para ver las consultas de este usuario');

        $this->consultaService->obtenerConsultasPorUsuario(1, 2, 1);
    }

    /** @test */
    public function test_it_can_get_consultas_by_propiedad()
    {
        $expected = [['id' => 1]];

        $this->policyMock->method('puedeVerDePropiedad')->willReturn(true);

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