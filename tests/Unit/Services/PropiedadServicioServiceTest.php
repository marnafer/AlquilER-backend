<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ConflictException;
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
use PHPUnit\Framework\TestCase;

final class PropiedadServicioServiceTest extends TestCase
{
    public function test_lista_servicios_de_una_propiedad(): void
    {
        $propiedad = new Propiedad();
        $propiedad->id = 1;

        $servicios = [
            ['id' => 1, 'servicio_id' => 1],
            ['id' => 2, 'servicio_id' => 2],
        ];

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $repository
            ->expects($this->once())
            ->method('getByPropiedad')
            ->with(1)
            ->willReturn($servicios);

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository
        );

        $resultado = $service->listar(1);

        $this->assertSame($servicios, $resultado);
    }

    public function test_listar_lanza_excepcion_si_la_propiedad_no_existe(): void
    {
        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $repository
            ->expects($this->never())
            ->method('getByPropiedad');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $service->listar(999);
    }

    public function test_listar_lanza_excepcion_si_el_id_de_propiedad_es_invalido(): void
    {
        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $repository
            ->expects($this->never())
            ->method('getByPropiedad');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository
        );

        $this->expectException(ValidationException::class);

        $service->listar(0);
    }

    public function test_lista_propiedades_de_un_servicio(): void
    {
        $servicio = new Servicio();
        $servicio->id = 1;

        $propiedades = [
            ['id' => 1, 'propiedad_id' => 1],
            ['id' => 2, 'propiedad_id' => 2],
        ];

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($servicio);

        $repository
            ->expects($this->once())
            ->method('getByServicio')
            ->with(1)
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            $repository,
            null,
            $servicioRepository
        );

        $resultado = $service->listarPorServicio(1);

        $this->assertSame($propiedades, $resultado);
    }

    public function test_listar_por_servicio_lanza_excepcion_si_el_servicio_no_existe(): void
    {
        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $repository
            ->expects($this->never())
            ->method('getByServicio');

        $service = $this->crearServicio(
            $repository,
            null,
            $servicioRepository
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('El servicio no existe');

        $service->listarPorServicio(999);
    }

    public function test_tiene_devuelve_true_si_la_relacion_existe(): void
    {
        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(true);

        $service = $this->crearServicio($repository);

        $resultado = $service->tiene(1, 2);

        $this->assertTrue($resultado);
    }

    public function test_tiene_devuelve_false_si_la_relacion_no_existe(): void
    {
        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(false);

        $service = $this->crearServicio($repository);

        $resultado = $service->tiene(1, 2);

        $this->assertFalse($resultado);
    }

    public function test_tiene_lanza_excepcion_si_los_ids_son_invalidos(): void
    {
        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $repository
            ->expects($this->never())
            ->method('exists');

        $service = $this->crearServicio($repository);

        $this->expectException(ValidationException::class);

        $service->tiene(0, 2);
    }

    public function test_asigna_un_servicio_a_una_propiedad(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 3
        ]);
        $propiedad->id = 1;

        $servicio = new Servicio();
        $servicio->id = 2;

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($servicio);

        $repository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('attach')
            ->with(1, 2)
            ->willReturn(true);

        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(3, 'servicio_asignado');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository,
            $logService,
            $policy
        );

        $resultado = $service->asignar(
            1,
            2,
            3,
            1
        );

        $this->assertSame([
            'propiedad_id' => 1,
            'servicio_id' => 2,
        ], $resultado);
    }

    public function test_asignar_lanza_excepcion_si_la_propiedad_no_existe(): void
    {
        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $policy
            ->expects($this->never())
            ->method('gestionar');

        $servicioRepository
            ->expects($this->never())
            ->method('findById');

        $repository
            ->expects($this->never())
            ->method('attach');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository,
            null,
            $policy
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $service->asignar(999, 2, 3, 1);
    }

    public function test_asignar_lanza_excepcion_si_el_servicio_no_existe(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 3
        ]);
        $propiedad->id = 1;

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $repository
            ->expects($this->never())
            ->method('exists');

        $repository
            ->expects($this->never())
            ->method('attach');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository,
            null,
            $policy
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('El servicio no existe');

        $service->asignar(1, 999, 3, 1);
    }

    public function test_asignar_lanza_excepcion_si_el_servicio_ya_esta_asignado(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 3
        ]);
        $propiedad->id = 1;

        $servicio = new Servicio();
        $servicio->id = 2;

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $servicioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($servicio);

        $repository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('attach');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository,
            null,
            $policy
        );

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage(
            'La propiedad ya tiene este servicio asignado'
        );

        $service->asignar(1, 2, 3, 1);
    }

    public function test_asignar_propaga_la_excepcion_si_no_tiene_permiso(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 99
        ]);
        $propiedad->id = 1;

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1)
            ->willThrowException(
                new ForbiddenException(
                    'No tienes permiso para gestionar esta propiedad'
                )
            );

        $servicioRepository
            ->expects($this->never())
            ->method('findById');

        $repository
            ->expects($this->never())
            ->method('attach');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository,
            null,
            $policy
        );

        $this->expectException(ForbiddenException::class);

        $service->asignar(1, 2, 3, 1);
    }

    public function test_desasigna_un_servicio_de_una_propiedad(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 3
        ]);
        $propiedad->id = 1;

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $repository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(true);

        $repository
            ->expects($this->once())
            ->method('detach')
            ->with(1, 2)
            ->willReturn(true);

        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(3, 'servicio_desasignado');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            null,
            $logService,
            $policy
        );

        $resultado = $service->desasignar(
            1,
            2,
            3,
            1
        );

        $this->assertTrue($resultado);
    }

    public function test_desasignar_lanza_excepcion_si_la_propiedad_no_existe(): void
    {
        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $policy
            ->expects($this->never())
            ->method('gestionar');

        $repository
            ->expects($this->never())
            ->method('exists');

        $repository
            ->expects($this->never())
            ->method('detach');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            null,
            null,
            $policy
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $service->desasignar(999, 2, 3, 1);
    }

    public function test_desasignar_lanza_excepcion_si_el_servicio_no_esta_asignado(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 3
        ]);
        $propiedad->id = 1;

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $repository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(false);

        $repository
            ->expects($this->never())
            ->method('detach');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            null,
            null,
            $policy
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'La propiedad no tiene este servicio asignado'
        );

        $service->desasignar(1, 2, 3, 1);
    }

    public function test_desasignar_propaga_la_excepcion_si_no_tiene_permiso(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 99
        ]);
        $propiedad->id = 1;

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1)
            ->willThrowException(
                new ForbiddenException(
                    'No tienes permiso para gestionar esta propiedad'
                )
            );

        $repository
            ->expects($this->never())
            ->method('exists');

        $repository
            ->expects($this->never())
            ->method('detach');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            null,
            null,
            $policy
        );

        $this->expectException(ForbiddenException::class);

        $service->desasignar(1, 2, 3, 1);
    }

    public function test_asigna_multiples_servicios(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 3
        ]);
        $propiedad->id = 1;

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
            'errores' => [],
        ];

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $servicioRepository
            ->expects($this->once())
            ->method('findByIds')
            ->with([1, 2, 3])
            ->willReturn($servicios);

        $repository
            ->expects($this->once())
            ->method('attachMultiple')
            ->with(1, [1, 2, 3])
            ->willReturn($resultadoEsperado);

        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(3, 'servicios_multiples_asignados');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository,
            $logService,
            $policy
        );

        $resultado = $service->asignarMultiples(
            1,
            [1, 2, 3],
            3,
            1
        );

        $this->assertSame($resultadoEsperado, $resultado);
    }

    public function test_asignar_multiples_lanza_excepcion_si_la_propiedad_no_existe(): void
    {
        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $policy
            ->expects($this->never())
            ->method('gestionar');

        $servicioRepository
            ->expects($this->never())
            ->method('findByIds');

        $repository
            ->expects($this->never())
            ->method('attachMultiple');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository,
            null,
            $policy
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $service->asignarMultiples(
            999,
            [1, 2],
            3,
            1
        );
    }

    public function test_asignar_multiples_lanza_excepcion_si_un_servicio_no_existe(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 3
        ]);
        $propiedad->id = 1;

        $servicio1 = new Servicio();
        $servicio1->id = 1;

        $servicio3 = new Servicio();
        $servicio3->id = 3;

        $servicios = new Collection([
            $servicio1,
            $servicio3,
        ]);

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $servicioRepository
            ->expects($this->once())
            ->method('findByIds')
            ->with([1, 2, 3])
            ->willReturn($servicios);

        $repository
            ->expects($this->never())
            ->method('attachMultiple');

        $logService = $this->createMock(
            LogActividadService::class
        );

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository,
            $logService,
            $policy
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Los siguientes servicios no existen: 2'
        );

        $service->asignarMultiples(
            1,
            [1, 2, 3],
            3,
            1
        );
    }

    public function test_asignar_multiples_lanza_excepcion_si_la_lista_esta_vacia(): void
    {
        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $repository
            ->expects($this->never())
            ->method('attachMultiple');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository
        );

        $this->expectException(ValidationException::class);

        $service->asignarMultiples(
            1,
            [],
            3,
            1
        );
    }

    public function test_asignar_multiples_lanza_excepcion_si_hay_ids_duplicados(): void
    {
        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $servicioRepository
            ->expects($this->never())
            ->method('findByIds');

        $repository
            ->expects($this->never())
            ->method('attachMultiple');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository
        );

        $this->expectException(ValidationException::class);

        $service->asignarMultiples(
            1,
            [1, 1],
            3,
            1
        );
    }

    public function test_sincroniza_servicios(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 3
        ]);
        $propiedad->id = 1;

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
            'mantenidos' => [],
        ];

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $servicioRepository
            ->expects($this->once())
            ->method('findByIds')
            ->with([1, 2, 3])
            ->willReturn($servicios);

        $repository
            ->expects($this->once())
            ->method('sync')
            ->with(1, [1, 2, 3])
            ->willReturn($resultadoEsperado);

        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(3, 'servicios_sincronizados');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository,
            $logService,
            $policy
        );

        $resultado = $service->sincronizar(
            1,
            [1, 2, 3],
            3,
            1
        );

        $this->assertSame($resultadoEsperado, $resultado);
    }

    public function test_sincroniza_servicios_con_lista_vacia(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 3
        ]);
        $propiedad->id = 1;

        $resultadoEsperado = [
            'agregados' => [],
            'eliminados' => [1, 2],
            'mantenidos' => [],
        ];

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $servicioRepository
            ->expects($this->never())
            ->method('findByIds');

        $repository
            ->expects($this->once())
            ->method('sync')
            ->with(1, [])
            ->willReturn($resultadoEsperado);

        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(3, 'servicios_sincronizados');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository,
            $logService,
            $policy
        );

        $resultado = $service->sincronizar(
            1,
            [],
            3,
            1
        );

        $this->assertSame($resultadoEsperado, $resultado);
    }

    public function test_sincronizar_lanza_excepcion_si_un_servicio_no_existe(): void
    {
        $propiedad = new Propiedad([
            'usuario_id' => 3
        ]);
        $propiedad->id = 1;

        $servicio1 = new Servicio();
        $servicio1->id = 1;

        $servicio3 = new Servicio();
        $servicio3->id = 3;

        $servicios = new Collection([
            $servicio1,
            $servicio3,
        ]);

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $policy = $this->createMock(
            PropiedadPolicy::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($propiedad, 3, 1);

        $servicioRepository
            ->expects($this->once())
            ->method('findByIds')
            ->with([1, 2, 3])
            ->willReturn($servicios);

        $repository
            ->expects($this->never())
            ->method('sync');

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository,
            $servicioRepository,
            $logService,
            $policy
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Los siguientes servicios no existen: 2'
        );

        $service->sincronizar(
            1,
            [1, 2, 3],
            3,
            1
        );
    }

    public function test_obtiene_ids_de_servicios_de_una_propiedad(): void
    {
        $propiedad = new Propiedad();
        $propiedad->id = 1;

        $ids = [1, 2, 3];

        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $repository
            ->expects($this->once())
            ->method('getServicioIdsByPropiedad')
            ->with(1)
            ->willReturn($ids);

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository
        );

        $resultado = $service->obtenerIds(1);

        $this->assertSame($ids, $resultado);
    }

    public function test_obtener_ids_lanza_excepcion_si_la_propiedad_no_existe(): void
    {
        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $repository
            ->expects($this->never())
            ->method('getServicioIdsByPropiedad');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $service->obtenerIds(999);
    }

    public function test_obtener_ids_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $repository
            ->expects($this->never())
            ->method('getServicioIdsByPropiedad');

        $service = $this->crearServicio(
            $repository,
            $propiedadRepository
        );

        $this->expectException(ValidationException::class);

        $service->obtenerIds(0);
    }

    private function crearServicio(
        ?PropiedadServicioRepositoryInterface $repository = null,
        ?PropiedadRepositoryInterface $propiedadRepository = null,
        ?ServicioRepositoryInterface $servicioRepository = null,
        ?LogActividadService $logService = null,
        ?PropiedadPolicy $policy = null
    ): PropiedadServicioService {
        return new PropiedadServicioService(
            $repository
                ?? $this->createMock(
                    PropiedadServicioRepositoryInterface::class
                ),
            $propiedadRepository
                ?? $this->createMock(
                    PropiedadRepositoryInterface::class
                ),
            $servicioRepository
                ?? $this->createMock(
                    ServicioRepositoryInterface::class
                ),
            $logService
                ?? $this->createMock(
                    LogActividadService::class
                ),
            $policy
                ?? $this->createMock(
                    PropiedadPolicy::class
                )
        );
    }
}