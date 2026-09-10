<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\BadRequestException;
use App\Models\Rol;
use App\Repositories\RolRepositoryInterface;
use App\Services\RolService;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Collection;

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

    public function test_lista_roles_y_devuelve_el_total(): void
    {
        $rol = new Rol(['nombre' => 'Administrador']);
        $roles = new Collection([$rol]);

        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('all')
            ->willReturn($roles);

        $service = new RolService($repository);

        $resultado = $service->listar();

        $this->assertSame($roles, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_actualiza_un_rol(): void
    {
        $rol = new Rol(['nombre' => 'Usuario']);
        $rol->id = 1;

        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($rol);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('moderador', 1)
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('update')
            ->with($rol, ['nombre' => 'moderador'])
            ->willReturn(true);

        $service = new RolService($repository);

        $service->actualizar(1, ['nombre' => ' moderador ']);

        $this->addToAssertionCount(1);
    }

    public function test_actualizar_lanza_excepcion_si_no_hay_campos(): void
    {
        $rol = new Rol(['nombre' => 'Usuario']);
        $rol->id = 1;

        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($rol);

        $repository
            ->expects($this->never())
            ->method('update');

        $service = new RolService($repository);

        $this->expectException(BadRequestException::class);

        $service->actualizar(1, []);
    }

    public function test_actualizar_lanza_excepcion_si_el_nombre_ya_existe(): void
    {
        $rol = new Rol(['nombre' => 'Usuario']);
        $rol->id = 1;

        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->method('findById')
            ->willReturn($rol);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Moderador', 1)
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('update');

        $service = new RolService($repository);

        $this->expectException(ConflictException::class);

        $service->actualizar(1, ['nombre' => 'Moderador']);
    }

    public function test_elimina_un_rol_sin_usuarios(): void
    {
        $rol = new Rol(['nombre' => 'Usuario']);
        $rol->id = 1;

        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->method('findById')
            ->willReturn($rol);

        $repository
            ->expects($this->once())
            ->method('hasUsers')
            ->with($rol)
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('delete')
            ->with($rol)
            ->willReturn(true);

        $service = new RolService($repository);

        $service->eliminar(1);

        $this->addToAssertionCount(1);
    }

    public function test_eliminar_lanza_excepcion_si_tiene_usuarios(): void
    {
        $rol = new Rol(['nombre' => 'Usuario']);
        $rol->id = 1;

        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->method('findById')
            ->willReturn($rol);

        $repository
            ->expects($this->once())
            ->method('hasUsers')
            ->with($rol)
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('delete');

        $service = new RolService($repository);

        $this->expectException(ConflictException::class);

        $service->eliminar(1);
    }

    public function test_restaura_un_rol_eliminado(): void
    {
        $rol = new Rol(['nombre' => 'Usuario']);
        $rol->id = 1;

        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($rol);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Usuario', 1)
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('restore')
            ->with($rol)
            ->willReturn(true);

        $service = new RolService($repository);

        $service->restaurar(1);

        $this->addToAssertionCount(1);
    }

    public function test_restaurar_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn(null);

        $service = new RolService($repository);

        $this->expectException(NotFoundException::class);

        $service->restaurar(1);
    }

    public function test_restaurar_lanza_excepcion_si_el_nombre_ya_esta_activo(): void
    {
        $rol = new Rol(['nombre' => 'Usuario']);
        $rol->id = 1;

        $repository = $this->createMock(RolRepositoryInterface::class);

        $repository
            ->method('findDeletedById')
            ->willReturn($rol);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Usuario', 1)
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('restore');

        $service = new RolService($repository);

        $this->expectException(ConflictException::class);

        $service->restaurar(1);
    }
}