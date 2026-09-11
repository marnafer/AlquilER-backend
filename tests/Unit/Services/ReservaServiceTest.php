<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ReservaService;
use App\Repositories\ReservaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Services\LogActividadService;
use App\Models\Propiedad;
use App\Models\Reserva;

class ReservaServiceTest extends TestCase
{
    private $reservaRepository;
    private $propiedadRepository;
    private $logService;
    private $reservaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservaRepository = $this->createMock(ReservaRepositoryInterface::class);
        $this->propiedadRepository = $this->createMock(PropiedadRepositoryInterface::class);
        $this->logService = $this->createMock(LogActividadService::class);

        $this->reservaService = new ReservaService(
            $this->reservaRepository,
            $this->propiedadRepository,
            $this->logService
        );
    }

    /** @test */
    public function test_it_can_list_reservas()
    {
        $filtros = ['estado' => 'pendiente'];
        $expected = [['id' => 1, 'estado' => 'pendiente']];

        $this->reservaRepository
            ->expects($this->once())
            ->method('getAll')
            ->with($filtros)
            ->willReturn($expected);

        $result = $this->reservaService->listarReservas($filtros);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function test_it_can_get_reserva_by_id()
    {
        $reservaMock = new Reserva(['id' => 1, 'estado' => 'pendiente']);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reservaMock);

        $result = $this->reservaService->obtenerReserva(1);
        $this->assertSame($reservaMock, $result);
    }

    /** @test */
    public function test_it_throws_exception_when_reserva_not_found()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Reserva no encontrada");
        $this->expectExceptionCode(404);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->reservaService->obtenerReserva(999);
    }

    /** @test */
    public function test_it_can_create_reserva()
    {
        $propiedadMock = new Propiedad([
            'id' => 1, 
            'disponible' => true,
            'titulo' => 'Casa de prueba',
            'precio' => 150000.00
        ]);
        
        $mañana = date('Y-m-d', strtotime('+1 day'));
        $pasado = date('Y-m-d', strtotime('+3 days'));

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedadMock);

        $this->reservaRepository
            ->expects($this->once())
            ->method('isAvailable')
            ->with(1, $mañana, $pasado)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('create')
            ->willReturn(1);

        $this->logService
            ->expects($this->once())
            ->method('registrar');

        $result = $this->reservaService->crearReserva([
            'propiedad_id' => 1,
            'usuario_id' => 2,
            'fecha_inicio_alquiler' => $mañana,
            'fecha_fin_alquiler' => $pasado
        ]);

        $this->assertEquals(1, $result);
    }

    /** @test */
    public function test_it_throws_exception_when_creating_reserva_with_non_existent_property()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("La propiedad no existe");
        $this->expectExceptionCode(404);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->reservaService->crearReserva([
            'propiedad_id' => 999,
            'usuario_id' => 2,
            'fecha_inicio_alquiler' => '2026-10-01',
            'fecha_fin_alquiler' => '2026-10-05'
        ]);
    }

    /** @test */
    public function test_it_throws_exception_when_property_not_available_for_rent()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("La propiedad no está disponible para alquiler");
        $this->expectExceptionCode(400);

        $propiedadMock = new Propiedad([
            'id' => 1, 
            'disponible' => false
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedadMock);

        $this->reservaService->crearReserva([
            'propiedad_id' => 1,
            'usuario_id' => 2,
            'fecha_inicio_alquiler' => '2026-10-01',
            'fecha_fin_alquiler' => '2026-10-05'
        ]);
    }

    /** @test */
    public function test_it_throws_exception_when_dates_are_invalid_on_create()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("La fecha de inicio no puede ser mayor a la fecha de fin");
        $this->expectExceptionCode(400);

        $propiedadMock = new Propiedad([
            'id' => 1, 
            'disponible' => true
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedadMock);

        $this->reservaService->crearReserva([
            'propiedad_id' => 1,
            'usuario_id' => 2,
            'fecha_inicio_alquiler' => '2026-10-05',
            'fecha_fin_alquiler' => '2026-10-01'
        ]);
    }

    /** @test */
    public function test_it_can_change_estado_reserva()
    {
        $reservaMock = new Reserva([
            'id' => 1, 
            'estado' => 'pendiente'
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reservaMock);

        $this->reservaRepository
            ->expects($this->once())
            ->method('cambiarEstado')
            ->with(1, 'confirmada')
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar');

        $result = $this->reservaService->cambiarEstadoReserva(1, 'confirmada', 3);
        $this->assertTrue($result);
    }

    /** @test */
    public function test_it_throws_exception_on_invalid_estado_transition()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("No se puede cambiar de 'rechazada' a 'confirmada'");
        $this->expectExceptionCode(400);

        $reservaMock = new Reserva([
            'id' => 1, 
            'estado' => 'rechazada'
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reservaMock);

        $this->reservaService->cambiarEstadoReserva(1, 'confirmada', 3);
    }

    /** @test */
    public function test_it_can_verificar_disponibilidad()
    {
        $propiedadMock = new Propiedad([
            'id' => 1, 
            'disponible' => true
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedadMock);

        $this->reservaRepository
            ->expects($this->once())
            ->method('isAvailable')
            ->with(1, '2026-10-01', '2026-10-05')
            ->willReturn(true);

        $result = $this->reservaService->verificarDisponibilidad(1, '2026-10-01', '2026-10-05');
        $this->assertTrue($result);
    }
}