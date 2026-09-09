<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\BadRequestException;
use App\Models\Servicio;
use App\Repositories\ServicioRepositoryInterface;
use App\Services\ServicioService;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Collection;

final class ServicioServiceTest extends TestCase
{
    public function test_crea_un_servicio(): void
    {
        $servicio = new Servicio(['nombre' => 'WiFi']);

        $repository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('WiFi')
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('create')
            ->with([
                'id' => null,
                'nombre' => 'WiFi',
            ])
            ->willReturn($servicio);

        $service = new ServicioService($repository);

        $resultado = $service->crear([
            'nombre' => '  WiFi  ',
        ]);

        $this->assertSame($servicio, $resultado);
    }

    public function test_crear_lanza_excepcion_si_el_servicio_ya_existe(): void
    {
        $repository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('WiFi')
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('create');

        $service = new ServicioService($repository);

        $this->expectException(ConflictException::class);

        $service->crear([
            'nombre' => 'WiFi',
        ]);
    }

    public function test_obtener_devuelve_un_servicio_existente(): void
    {
        $servicio = new Servicio(['nombre' => 'Pileta']);
        $servicio->id = 1;

        $repository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($servicio);

        $service = new ServicioService($repository);

        $this->assertSame($servicio, $service->obtener(1));
    }

    public function test_obtener_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository
            ->expects($this->never())
            ->method('findById');

        $service = new ServicioService($repository);

        $this->expectException(ValidationException::class);

        $service->obtener('abc');
    }

    public function test_obtener_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $service = new ServicioService($repository);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Servicio no encontrado');

        $service->obtener(1);
    }

    public function test_lista_servicios_y_devuelve_el_total(): void
    {
        $servicio = new Servicio(['nombre' => 'WiFi']);
        $servicios = new Collection([$servicio]);

        $repository = $this->createMock(ServicioRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('all')
            ->willReturn($servicios);

        $service = new ServicioService($repository);

        $resultado = $service->listar();

        $this->assertSame($servicios, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_actualiza_un_servicio(): void
    {
        $servicio = new Servicio(['nombre' => 'WiFi']);
        $servicio->id = 1;

        $repository = $this->createMock(ServicioRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($servicio);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Gimnasio', 1)
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('update')
            ->with($servicio, ['nombre' => 'Gimnasio'])
            ->willReturn(true);

        $service = new ServicioService($repository);

        $resultado = $service->actualizar(1, [
            'nombre' => '  Gimnasio  ',
        ]);

        $this->assertSame($servicio, $resultado);
    }

    public function test_actualizar_lanza_excepcion_si_no_hay_campos(): void
    {
        $servicio = new Servicio(['nombre' => 'WiFi']);
        $servicio->id = 1;

        $repository = $this->createMock(ServicioRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($servicio);

        $repository
            ->expects($this->never())
            ->method('update');

        $service = new ServicioService($repository);

        $this->expectException(BadRequestException::class);

        $service->actualizar(1, []);
    }

    public function test_actualizar_lanza_excepcion_si_el_nombre_ya_existe(): void
    {
        $servicio = new Servicio(['nombre' => 'WiFi']);
        $servicio->id = 1;

        $repository = $this->createMock(ServicioRepositoryInterface::class);

        $repository
            ->method('findById')
            ->willReturn($servicio);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Gimnasio', 1)
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('update');

        $service = new ServicioService($repository);

        $this->expectException(ConflictException::class);

        $service->actualizar(1, [
            'nombre' => 'Gimnasio',
        ]);
    }

    public function test_elimina_un_servicio_sin_propiedades(): void
    {
        $servicio = new Servicio(['nombre' => 'WiFi']);
        $servicio->id = 1;

        $repository = $this->createMock(ServicioRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($servicio);

        $repository
            ->expects($this->once())
            ->method('hasProperties')
            ->with($servicio)
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('delete')
            ->with($servicio)
            ->willReturn(true);

        $service = new ServicioService($repository);

        $service->eliminar(1);

        $this->addToAssertionCount(1);
    }

    public function test_eliminar_lanza_excepcion_si_tiene_propiedades(): void
    {
        $servicio = new Servicio(['nombre' => 'WiFi']);
        $servicio->id = 1;

        $repository = $this->createMock(ServicioRepositoryInterface::class);

        $repository
            ->method('findById')
            ->willReturn($servicio);

        $repository
            ->expects($this->once())
            ->method('hasProperties')
            ->with($servicio)
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('delete');

        $service = new ServicioService($repository);

        $this->expectException(ConflictException::class);

        $service->eliminar(1);
    }

    public function test_restaura_un_servicio_eliminado(): void
    {
        $servicio = new Servicio(['nombre' => 'WiFi']);
        $servicio->id = 1;

        $repository = $this->createMock(ServicioRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($servicio);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('WiFi', 1)
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('restore')
            ->with($servicio)
            ->willReturn(true);

        $service = new ServicioService($repository);

        $service->restaurar(1);

        $this->addToAssertionCount(1);
    }

    public function test_restaurar_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(ServicioRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn(null);

        $repository
            ->expects($this->never())
            ->method('restore');

        $service = new ServicioService($repository);

        $this->expectException(NotFoundException::class);

        $service->restaurar(1);
    }

    public function test_restaurar_lanza_excepcion_si_el_nombre_ya_esta_activo(): void
    {
        $servicio = new Servicio(['nombre' => 'WiFi']);
        $servicio->id = 1;

        $repository = $this->createMock(ServicioRepositoryInterface::class);

        $repository
            ->method('findDeletedById')
            ->willReturn($servicio);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('WiFi', 1)
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('restore');

        $service = new ServicioService($repository);

        $this->expectException(ConflictException::class);

        $service->restaurar(1);
    }
}