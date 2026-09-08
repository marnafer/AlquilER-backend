<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Provincia;
use App\Repositories\ProvinciaRepositoryInterface;
use App\Services\ProvinciaService;
use PHPUnit\Framework\TestCase;

final class ProvinciaServiceTest extends TestCase
{
    public function test_crea_una_provincia(): void
    {
        $provincia = new Provincia(['nombre' => 'Buenos Aires']);

        $repository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Buenos Aires')
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('create')
            ->with([
                'id' => null,
                'nombre' => 'Buenos Aires',
            ])
            ->willReturn($provincia);

        $service = new ProvinciaService($repository);

        $resultado = $service->crear([
            'nombre' => ' buenos   aires ',
        ]);

        $this->assertSame($provincia, $resultado);
    }

    public function test_crear_lanza_excepcion_si_ya_existe(): void
    {
        $repository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Buenos Aires')
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('create');

        $service = new ProvinciaService($repository);

        $this->expectException(ConflictException::class);

        $service->crear([
            'nombre' => 'Buenos Aires',
        ]);
    }

    public function test_obtener_devuelve_una_provincia_existente(): void
    {
        $provincia = new Provincia(['nombre' => 'Córdoba']);
        $provincia->id = 1;

        $repository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($provincia);

        $service = new ProvinciaService($repository);

        $this->assertSame($provincia, $service->obtener(1));
    }

    public function test_obtener_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $service = new ProvinciaService($repository);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Provincia no encontrada');

        $service->obtener(1);
    }

    public function test_obtener_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $repository
            ->expects($this->never())
            ->method('findById');

        $service = new ProvinciaService($repository);

        $this->expectException(ValidationException::class);

        $service->obtener('abc');
    }
}