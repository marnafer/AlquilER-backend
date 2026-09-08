<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Rol;
use App\Repositories\RolRepositoryInterface;
use App\Services\RolService;
use PHPUnit\Framework\TestCase;

final class RolServiceTest extends TestCase
{
    public function test_crea_un_rol(): void
    {
        $rol = new Rol(['nombre' => 'Administrador']);

        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Administrador')
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('create')
            ->with([
                'id' => null,
                'nombre' => 'Administrador',
            ])
            ->willReturn($rol);

        $service = new RolService($repository);

        $resultado = $service->crear([
            'nombre' => ' Administrador ',
        ]);

        $this->assertSame($rol, $resultado);
    }

    public function test_crear_lanza_excepcion_si_el_rol_ya_existe(): void
    {
        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Administrador')
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('create');

        $service = new RolService($repository);

        $this->expectException(ConflictException::class);

        $service->crear([
            'nombre' => 'Administrador',
        ]);
    }

    public function test_obtener_devuelve_un_rol_existente(): void
    {
        $rol = new Rol(['nombre' => 'Usuario']);
        $rol->id = 1;

        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($rol);

        $service = new RolService($repository);

        $this->assertSame($rol, $service->obtener(1));
    }

    public function test_obtener_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $service = new RolService($repository);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Rol no encontrado');

        $service->obtener(1);
    }

    public function test_obtener_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->expects($this->never())
            ->method('findById');

        $service = new RolService($repository);

        $this->expectException(ValidationException::class);

        $service->obtener('abc');
    }
}