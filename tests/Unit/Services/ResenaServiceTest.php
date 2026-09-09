<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ResenaService;
use App\Repositories\ResenaRepositoryInterface;
use App\Repositories\ReservaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\LogActividadService;

class ResenaServiceTest extends TestCase
{
    private $resenaRepository;
    private $reservaRepository;
    private $propiedadRepository;
    private $usuarioRepository;
    private $logService;
    private $resenaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resenaRepository = $this->createMock(ResenaRepositoryInterface::class);
        $this->reservaRepository = $this->createMock(ReservaRepositoryInterface::class);
        $this->propiedadRepository = $this->createMock(PropiedadRepositoryInterface::class);
        $this->usuarioRepository = $this->createMock(UsuarioRepositoryInterface::class);
        $this->logService = $this->createMock(LogActividadService::class);

        $this->resenaService = new ResenaService(
            $this->resenaRepository,
            $this->reservaRepository,
            $this->propiedadRepository,
            $this->usuarioRepository,
            $this->logService
        );
    }

    /** @test */
    public function it_can_list_resenas()
    {
        $filtros = ['tipo' => 'propiedad'];
        $expected = [['id' => 1, 'calificacion' => 5]];

        $this->resenaRepository
            ->expects($this->once())
            ->method('getAll')
            ->with($filtros)
            ->willReturn($expected);

        $result = $this->resenaService->listarResenas($filtros);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_can_create_resena_de_propiedad()
    {
        $reservaMock = (object) [
            'id' => 5,
            'estado' => 'finalizada',
            'propiedad_id' => 1,
            'usuario_id' => 2
        ];
        $propiedadMock = (object) ['id' => 1, 'usuario_id' => 3];

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($reservaMock);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedadMock);

        $this->resenaRepository
            ->expects($this->once())
            ->method('existePorReservaYTipo')
            ->with(5, 'propiedad')
            ->willReturn(false);

        $this->resenaRepository
            ->expects($this->once())
            ->method('create')
            ->willReturn(1);

        $this->logService
            ->expects($this->once())
            ->method('registrar');

        $result = $this->resenaService->crearResena([
            'reserva_id' => 5,
            'tipo' => 'propiedad',
            'calificacion' => 5
        ]);

        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_can_create_resena_de_inquilino()
    {
        $reservaMock = (object) [
            'id' => 5,
            'estado' => 'finalizada',
            'propiedad_id' => 1,
            'usuario_id' => 2
        ];
        $propiedadMock = (object) ['id' => 1, 'usuario_id' => 3];

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($reservaMock);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedadMock);

        $this->resenaRepository
            ->expects($this->once())
            ->method('existePorReservaYTipo')
            ->with(5, 'inquilino')
            ->willReturn(false);

        $this->resenaRepository
            ->expects($this->once())
            ->method('create')
            ->willReturn(1);

        $this->logService
            ->expects($this->once())
            ->method('registrar');

        $result = $this->resenaService->crearResena([
            'reserva_id' => 5,
            'tipo' => 'inquilino',
            'calificacion' => 4
        ]);

        $this->assertEquals(1, $result);
    }

    /** @test */
    public function it_throws_exception_when_reserva_not_finalizada()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Solo se pueden calificar reservas finalizadas");
        $this->expectExceptionCode(400);

        $reservaMock = (object) [
            'id' => 5,
            'estado' => 'pendiente',
            'propiedad_id' => 1
        ];

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($reservaMock);

        $this->resenaService->crearResena([
            'reserva_id' => 5,
            'tipo' => 'propiedad',
            'calificacion' => 5
        ]);
    }

    /** @test */
    public function it_throws_exception_when_duplicate_resena()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Esta reserva ya tiene una reseña de tipo 'propiedad'");
        $this->expectExceptionCode(409);

        $reservaMock = (object) [
            'id' => 5,
            'estado' => 'finalizada',
            'propiedad_id' => 1,
            'usuario_id' => 2
        ];
        $propiedadMock = (object) ['id' => 1, 'usuario_id' => 3];

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($reservaMock);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedadMock);

        $this->resenaRepository
            ->expects($this->once())
            ->method('existePorReservaYTipo')
            ->with(5, 'propiedad')
            ->willReturn(true);

        $this->resenaService->crearResena([
            'reserva_id' => 5,
            'tipo' => 'propiedad',
            'calificacion' => 5
        ]);
    }

    /** @test */
    public function it_can_get_promedio_by_propiedad()
    {
        $this->resenaRepository
            ->expects($this->once())
            ->method('getPromedioByPropiedad')
            ->with(1)
            ->willReturn(4.5);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn((object) ['id' => 1]);

        $result = $this->resenaService->obtenerPromedioPropiedad(1);
        $this->assertEquals(4.5, $result);
    }

    /** @test */
    public function it_can_get_promedio_by_usuario()
    {
        $this->resenaRepository
            ->expects($this->once())
            ->method('getPromedioByUsuario')
            ->with(2)
            ->willReturn(4.2);

        $this->usuarioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn((object) ['id' => 2]);

        $result = $this->resenaService->obtenerPromedioUsuario(2);
        $this->assertEquals(4.2, $result);
    }
}