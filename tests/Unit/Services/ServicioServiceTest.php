<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Servicio;
use App\Repositories\ServicioRepositoryInterface;
use App\Services\ServicioService;
use PHPUnit\Framework\TestCase;

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
}