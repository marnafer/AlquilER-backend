<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Propiedad;
use App\Models\Servicio;
use App\Policies\PropiedadPolicy;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\PropiedadServicioRepositoryInterface;
use App\Repositories\ServicioRepositoryInterface;
use App\Services\LogActividadService;
use App\Services\PropiedadServicioService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PropiedadServicioServiceTest extends TestCase
{
    private $propiedadServicioRepository;
    private $propiedadRepository;
    private $servicioRepository;
    private $logService;
    private $policy;
    private $propiedadServicioService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propiedadServicioRepository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $this->propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $this->servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $this->logService = $this->createMock(
            LogActividadService::class
        );

        $this->policy = $this->createMock(
            PropiedadPolicy::class
        );

        $this->propiedadServicioService = new PropiedadServicioService(
            $this->propiedadServicioRepository,
            $this->propiedadRepository,
            $this->servicioRepository,
            $this->logService,
            $this->policy
        );
    }

    public function test_it_can_get_servicios_by_propiedad(): void
    {
        $expected = [
            ['id' => 1, 'servicio_id' => 1]
        ];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(
                new Propiedad([
                    'id' => 1
                ])
            );

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('getByPropiedad')
            ->with(1)
            ->willReturn($expected);

        $result = $this->propiedadServicioService
            ->obtenerServiciosPorPropiedad(1);

        $this->assertSame($expected, $result);
    }

    public function test_it_throws_exception_when_property_not_found_for_get_servicios(): void
    {
        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('getByPropiedad');

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $this->propiedadServicioService
            ->obtenerServiciosPorPropiedad(999);
    }

    public function test_it_throws_validation_exception_when_property_id_is_invalid_for_get_servicios(): void
    {
        $this->propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('getByPropiedad');

        $this->expectException(ValidationException::class);

        $this->propiedadServicioService
            ->obtenerServiciosPorPropiedad(0);
    }

    public function test_it_can_get_propiedades_by_servicio(): void
    {
        $expected = [
            ['id' => 1, 'propiedad_id' => 1]
        ];

        $this->servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(
                new Servicio([
                    'id' => 1
                ])
            );

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('getByServicio')
            ->with(1)
            ->willReturn($expected);

        $result = $this->propiedadServicioService
            ->obtenerPropiedadesPorServicio(1);

        $this->assertSame($expected, $result);
    }

    public function test_it_throws_exception_when_service_not_found_for_get_propiedades(): void
    {
        $this->servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('getByServicio');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('El servicio no existe');

        $this->propiedadServicioService
            ->obtenerPropiedadesPorServicio(999);
    }

    public function test_it_can_check_if_property_has_service(): void
    {
        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(true);

        $result = $this->propiedadServicioService
            ->tieneServicio(1, 2);

        $this->assertTrue($result);
    }

    public function test_it_returns_false_when_property_does_not_have_service(): void
    {
        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(false);

        $result = $this->propiedadServicioService
            ->tieneServicio(1, 2);

        $this->assertFalse($result);
    }

    public function test_it_throws_validation_exception_when_checking_service_with_invalid_ids(): void
    {
        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('exists');

        $this->expectException(ValidationException::class);

        $this->propiedadServicioService
            ->tieneServicio(0, 2);
    }

    public function test_it_can_asignar_servicio_as_owner(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $servicio = new Servicio([
            'id' => 2
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $this->servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($servicio);

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('attach')
            ->with(1, 2)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(3, 'servicio_asignado');

        $result = $this->propiedadServicioService
            ->asignarServicio(1, 2, 3, 1);

        $this->assertTrue($result);
    }

    public function test_it_can_asignar_servicio_as_admin(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 99
        ]);

        $servicio = new Servicio([
            'id' => 2
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 2);

        $this->servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($servicio);

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('attach')
            ->with(1, 2)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(3, 'servicio_asignado');

        $result = $this->propiedadServicioService
            ->asignarServicio(1, 2, 3, 2);

        $this->assertTrue($result);
    }

    public function test_it_throws_exception_when_property_not_found_for_asignar(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->policy
            ->expects($this->never())
            ->method('gestionar');

        $this->servicioRepository
            ->expects($this->never())
            ->method('findById');

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('attach');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $this->propiedadServicioService
            ->asignarServicio(999, 1, 3, 1);
    }

    public function test_it_throws_exception_when_service_not_found_for_asignar(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $this->servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('attach');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('El servicio no existe');

        $this->propiedadServicioService
            ->asignarServicio(1, 999, 3, 1);
    }

    public function test_it_propagates_forbidden_exception_when_asignar_is_not_authorized(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 99
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1)
            ->willThrowException(
                new ForbiddenException(
                    'No tienes permiso para gestionar esta propiedad'
                )
            );

        $this->servicioRepository
            ->expects($this->never())
            ->method('findById');

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('attach');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(ForbiddenException::class);

        $this->propiedadServicioService
            ->asignarServicio(1, 2, 3, 1);
    }

    public function test_it_can_desasignar_servicio(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

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
            ->method('registrar')
            ->with(3, 'servicio_desasignado');

        $result = $this->propiedadServicioService
            ->desasignarServicio(1, 2, 3, 1);

        $this->assertTrue($result);
    }

    public function test_it_throws_exception_when_property_not_found_for_desasignar(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->policy
            ->expects($this->never())
            ->method('gestionar');

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('exists');

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('detach');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $this->propiedadServicioService
            ->desasignarServicio(999, 2, 3, 1);
    }

    public function test_it_throws_exception_when_service_is_not_assigned_for_desasignar(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 999)
            ->willReturn(false);

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('detach');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'La propiedad no tiene este servicio asignado'
        );

        $this->propiedadServicioService
            ->desasignarServicio(1, 999, 3, 1);
    }

    public function test_it_propagates_forbidden_exception_when_desasignar_is_not_authorized(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 99
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1)
            ->willThrowException(
                new ForbiddenException(
                    'No tienes permiso para gestionar esta propiedad'
                )
            );

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('exists');

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('detach');

        $this->expectException(ForbiddenException::class);

        $this->propiedadServicioService
            ->desasignarServicio(1, 2, 3, 1);
    }

    public function test_it_can_asignar_multiple_servicios(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $servicio1 = new Servicio();
        $servicio1->id = 1;

        $servicio2 = new Servicio();
        $servicio2->id = 2;

        $servicio3 = new Servicio();
        $servicio3->id = 3;

        $servicios = new Collection([
            $servicio1,
            $servicio2,
            $servicio3,
        ]);

        $resultadoEsperado = [
            'asignados' => [1, 2],
            'duplicados' => [3],
            'errores' => []
        ];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $this->servicioRepository
            ->expects($this->once())
            ->method('findByIds')
            ->with([1, 2, 3])
            ->willReturn($servicios);

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('attachMultiple')
            ->with(1, [1, 2, 3])
            ->willReturn($resultadoEsperado);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(3, 'servicios_multiples_asignados');

        $result = $this->propiedadServicioService
            ->asignarMultiplesServicios(1, [1, 2, 3], 3, 1);

        $this->assertSame($resultadoEsperado, $result);
    }

    public function test_it_throws_exception_when_property_not_found_for_multiple_assignment(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->policy
            ->expects($this->never())
            ->method('gestionar');

        $this->servicioRepository
            ->expects($this->never())
            ->method('findByIds');

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('attachMultiple');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $this->propiedadServicioService
            ->asignarMultiplesServicios(999, [1, 2], 3, 1);
    }

    public function test_it_throws_exception_when_one_service_does_not_exist_for_multiple_assignment(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $servicio1 = new Servicio();
        $servicio1->id = 1;

        $servicio3 = new Servicio();
        $servicio3->id = 3;

        $servicios = new Collection([
            $servicio1,
            $servicio3,
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $this->servicioRepository
            ->expects($this->once())
            ->method('findByIds')
            ->with([1, 2, 3])
            ->willReturn($servicios);

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('attachMultiple');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Los siguientes servicios no existen: 2'
        );

        $this->propiedadServicioService
            ->asignarMultiplesServicios(1, [1, 2, 3], 3, 1);
    }

    public function test_it_can_sync_servicios(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $servicio1 = new Servicio();
        $servicio1->id = 1;

        $servicio2 = new Servicio();
        $servicio2->id = 2;

        $servicio3 = new Servicio();
        $servicio3->id = 3;

        $servicios = new Collection([
            $servicio1,
            $servicio2,
            $servicio3,
        ]);

        $resultadoEsperado = [
            'agregados' => [1, 2],
            'eliminados' => [3],
            'mantenidos' => []
        ];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $this->servicioRepository
            ->expects($this->once())
            ->method('findByIds')
            ->with([1, 2, 3])
            ->willReturn($servicios);

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('sync')
            ->with(1, [1, 2, 3])
            ->willReturn($resultadoEsperado);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(3, 'servicios_sincronizados');

        $result = $this->propiedadServicioService
            ->sincronizarServicios(1, [1, 2, 3], 3, 1);

        $this->assertSame($resultadoEsperado, $result);
    }

    public function test_it_can_sync_servicios_with_empty_list(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $resultadoEsperado = [
            'agregados' => [],
            'eliminados' => [1, 2],
            'mantenidos' => []
        ];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $this->servicioRepository
            ->expects($this->never())
            ->method('findByIds');

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('sync')
            ->with(1, [])
            ->willReturn($resultadoEsperado);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(3, 'servicios_sincronizados');

        $result = $this->propiedadServicioService
            ->sincronizarServicios(1, [], 3, 1);

        $this->assertSame($resultadoEsperado, $result);
    }

    public function test_it_throws_exception_when_one_service_does_not_exist_for_sync(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $servicio1 = new Servicio();
        $servicio1->id = 1;

        $servicio3 = new Servicio();
        $servicio3->id = 3;

        $servicios = new Collection([
            $servicio1,
            $servicio3,
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $this->servicioRepository
            ->expects($this->once())
            ->method('findByIds')
            ->with([1, 2, 3])
            ->willReturn($servicios);

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('sync');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Los siguientes servicios no existen: 2'
        );

        $this->propiedadServicioService
            ->sincronizarServicios(1, [1, 2, 3], 3, 1);
    }

    public function test_it_throws_exception_when_property_not_found_for_sync(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->policy
            ->expects($this->never())
            ->method('gestionar');

        $this->servicioRepository
            ->expects($this->never())
            ->method('findByIds');

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('sync');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $this->propiedadServicioService
            ->sincronizarServicios(999, [1, 2], 3, 1);
    }

    public function test_it_can_get_service_ids_by_property(): void
    {
        $expected = [1, 2, 3];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(
                new Propiedad([
                    'id' => 1
                ])
            );

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('getServicioIdsByPropiedad')
            ->with(1)
            ->willReturn($expected);

        $result = $this->propiedadServicioService
            ->obtenerIdsServiciosPorPropiedad(1);

        $this->assertSame($expected, $result);
    }

    public function test_it_throws_exception_when_property_not_found_for_get_service_ids(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('getServicioIdsByPropiedad');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $this->propiedadServicioService
            ->obtenerIdsServiciosPorPropiedad(999);
    }

    public function test_it_throws_validation_exception_for_invalid_service_ids_in_multiple_assignment(): void
    {
        $this->propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $this->policy
            ->expects($this->never())
            ->method('gestionar');

        $this->servicioRepository
            ->expects($this->never())
            ->method('findByIds');

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('attachMultiple');

        $this->expectException(ValidationException::class);

        $this->propiedadServicioService
            ->asignarMultiplesServicios(1, [], 3, 1);
    }

    public function test_it_throws_validation_exception_for_duplicate_service_ids(): void
    {
        $this->propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $this->servicioRepository
            ->expects($this->never())
            ->method('findByIds');

        $this->propiedadServicioRepository
            ->expects($this->never())
            ->method('attachMultiple');

        $this->expectException(ValidationException::class);

        $this->propiedadServicioService
            ->asignarMultiplesServicios(1, [1, 1], 3, 1);
    }

    public function test_it_throws_validation_exception_for_invalid_property_id(): void
    {
        $this->propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(ValidationException::class);

        $this->propiedadServicioService
            ->asignarServicio(0, 2, 3, 1);
    }

    public function test_it_does_not_log_when_asignar_returns_false(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $servicio = new Servicio([
            'id' => 2
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $this->servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($servicio);

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('attach')
            ->with(1, 2)
            ->willReturn(false);

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $result = $this->propiedadServicioService
            ->asignarServicio(1, 2, 3, 1);

        $this->assertFalse($result);
    }

    public function test_it_does_not_log_when_desasignar_returns_false(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(true);

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('detach')
            ->with(1, 2)
            ->willReturn(false);

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $result = $this->propiedadServicioService
            ->desasignarServicio(1, 2, 3, 1);

        $this->assertFalse($result);
    }

    public function test_it_does_not_log_when_multiple_assignment_has_no_new_assignments(): void
    {
        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 3
        ]);

        $servicio1 = new Servicio();
        $servicio1->id = 1;

        $servicio2 = new Servicio();
        $servicio2->id = 2;

        $servicios = new Collection([
            $servicio1,
            $servicio2,
        ]);

        $resultado = [
            'asignados' => [],
            'duplicados' => [1, 2],
            'errores' => []
        ];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $this->servicioRepository
            ->expects($this->once())
            ->method('findByIds')
            ->with([1, 2])
            ->willReturn($servicios);

        $this->propiedadServicioRepository
            ->expects($this->once())
            ->method('attachMultiple')
            ->with(1, [1, 2])
            ->willReturn($resultado);

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $result = $this->propiedadServicioService
            ->asignarMultiplesServicios(1, [1, 2], 3, 1);

        $this->assertSame($resultado, $result);
    }
}