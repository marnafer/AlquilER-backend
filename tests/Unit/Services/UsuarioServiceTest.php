<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Usuario;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\LogActividadService;
use App\Services\UsuarioService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class UsuarioServiceTest extends TestCase
{
    public function test_lista_usuarios_y_devuelve_el_total(): void
    {
        $usuarios = new Collection([
            new Usuario(['nombre' => 'Ana']),
            new Usuario(['nombre' => 'Luis']),
        ]);

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('all')
            ->willReturn($usuarios);

        $resultado = (new UsuarioService(
            $repository,
            $logService
        ))->listar();

        $this->assertSame(
            $usuarios,
            $resultado['items']
        );

        $this->assertSame(
            2,
            $resultado['total']
        );
    }

    public function test_obtener_devuelve_un_usuario_existente(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
        ]);

        $usuario->id = 1;

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $resultado = (new UsuarioService(
            $repository,
            $logService
        ))->obtener(1);

        $this->assertSame(
            $usuario,
            $resultado
        );
    }

    public function test_obtener_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->never())
            ->method('findById');

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            ValidationException::class
        );

        $service->obtener('abc');
    }

    public function test_obtener_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            NotFoundException::class
        );

        $this->expectExceptionMessage(
            'Usuario no encontrado'
        );

        $service->obtener(1);
    }

    public function test_obtener_con_rol_devuelve_un_usuario_existente(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
        ]);

        $usuario->id = 1;

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findByIdWithRole')
            ->with(1)
            ->willReturn($usuario);

        $resultado = (new UsuarioService(
            $repository,
            $logService
        ))->obtenerConRol(1);

        $this->assertSame(
            $usuario,
            $resultado
        );
    }

    public function test_obtener_con_rol_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->never())
            ->method('findByIdWithRole');

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            ValidationException::class
        );

        $service->obtenerConRol('abc');
    }

    public function test_obtener_con_rol_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findByIdWithRole')
            ->with(1)
            ->willReturn(null);

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            NotFoundException::class
        );

        $this->expectExceptionMessage(
            'Usuario no encontrado'
        );

        $service->obtenerConRol(1);
    }

    public function test_actualiza_un_usuario(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'email' => 'ana@example.com',
        ]);

        $usuario->id = 1;

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with(
                'nuevo@example.com',
                1
            )
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('update')
            ->with(
                $usuario,
                [
                    'nombre' => 'Ana Maria',
                    'email' => 'nuevo@example.com',
                ]
            )
            ->willReturn(true);

        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                1,
                'Actualización de usuario'
            );

        (new UsuarioService(
            $repository,
            $logService
        ))->actualizar(1, [
            'nombre' => ' ana maria ',
            'email' => ' NUEVO@EXAMPLE.COM ',
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_actualizar_encripta_la_contrasena(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
        ]);

        $usuario->id = 1;

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $repository
            ->expects($this->once())
            ->method('update')
            ->with(
                $usuario,
                $this->callback(
                    function (array $data): bool {
                        return isset($data['contrasena'])
                            && password_verify(
                                'secreto',
                                $data['contrasena']
                            );
                    }
                )
            )
            ->willReturn(true);

        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                1,
                'Actualización de usuario'
            );

        (new UsuarioService(
            $repository,
            $logService
        ))->actualizar(1, [
            'contrasena' => 'secreto',
        ]);
    }

    public function test_actualizar_lanza_excepcion_si_no_hay_campos(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
        ]);

        $usuario->id = 1;

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $repository
            ->expects($this->never())
            ->method('update');

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            BadRequestException::class
        );

        $service->actualizar(1, []);
    }

    public function test_actualizar_lanza_excepcion_si_no_hay_campos_actualizables(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
        ]);

        $usuario->id = 1;

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $repository
            ->expects($this->never())
            ->method('update');

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            BadRequestException::class
        );

        $service->actualizar(1, [
            'rol_id' => 2,
        ]);
    }

    public function test_actualizar_lanza_excepcion_si_los_datos_son_invalidos(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
        ]);

        $usuario->id = 1;

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $repository
            ->expects($this->never())
            ->method('existsByEmail');

        $repository
            ->expects($this->never())
            ->method('update');

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            ValidationException::class
        );

        $service->actualizar(1, [
            'nombre' => 'A',
        ]);
    }

    public function test_actualizar_lanza_conflicto_si_el_email_ya_existe(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
        ]);

        $usuario->id = 1;

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with(
                'otro@example.com',
                1
            )
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('update');

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            ConflictException::class
        );

        $this->expectExceptionMessage(
            'El email ya está registrado'
        );

        $service->actualizar(1, [
            'email' => 'otro@example.com',
        ]);
    }

    public function test_elimina_un_usuario_y_registra_la_actividad(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
        ]);

        $usuario->id = 1;

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $repository
            ->expects($this->once())
            ->method('delete')
            ->with($usuario)
            ->willReturn(true);

        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                1,
                'Eliminación de usuario'
            );

        (new UsuarioService(
            $repository,
            $logService
        ))->eliminar(1);

        $this->addToAssertionCount(1);
    }

    public function test_eliminar_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $repository
            ->expects($this->never())
            ->method('delete');

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            NotFoundException::class
        );

        $service->eliminar(1);
    }

    public function test_restaurar_un_usuario_eliminado_y_registra_la_actividad(): void
    {
        $usuario = new Usuario([
            'email' => 'ana@example.com',
        ]);

        $usuario->id = 1;

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($usuario);

        $repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('ana@example.com')
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('restore')
            ->with($usuario)
            ->willReturn(true);

        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                1,
                'Restauración de usuario'
            );

        (new UsuarioService(
            $repository,
            $logService
        ))->restaurar(1);

        $this->addToAssertionCount(1);
    }

    public function test_restaurar_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->never())
            ->method('findDeletedById');

        $repository
            ->expects($this->never())
            ->method('restore');

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            ValidationException::class
        );

        $service->restaurar('abc');
    }

    public function test_restaurar_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn(null);

        $repository
            ->expects($this->never())
            ->method('existsByEmail');

        $repository
            ->expects($this->never())
            ->method('restore');

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            NotFoundException::class
        );

        $service->restaurar(1);
    }

    public function test_restaurar_lanza_conflicto_si_el_email_ya_esta_registrado(): void
    {
        $usuario = new Usuario([
            'email' => 'ana@example.com',
        ]);

        $usuario->id = 1;

        $repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($usuario);

        $repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('ana@example.com')
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('restore');

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = new UsuarioService(
            $repository,
            $logService
        );

        $this->expectException(
            ConflictException::class
        );

        $this->expectExceptionMessage(
            'Ya existe un usuario activo con ese email'
        );

        $service->restaurar(1);
    }
}