<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\ForbiddenException;
use App\Models\Rol;
use App\Models\Usuario;
use App\Policies\UsuarioPolicy;
use App\Repositories\UsuarioRepositoryInterface;
use App\Repositories\ConsultaRepositoryInterface;
use App\Repositories\ReservaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\ResenaRepositoryInterface;
use App\Services\LogActividadService;
use App\Services\UsuarioService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UsuarioServiceTest extends TestCase
{
    /** @var UsuarioRepositoryInterface&MockObject */
    private $repository;

    /** @var LogActividadService&MockObject */
    private $logService;

    /** @var ConsultaRepositoryInterface&MockObject */
    private $consultaRepository;

    /** @var ReservaRepositoryInterface&MockObject */
    private $reservaRepository;

    /** @var PropiedadRepositoryInterface&MockObject */
    private $propiedadRepository;

    /** @var ResenaRepositoryInterface&MockObject */
    private $resenaRepository;
    
    private UsuarioPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $this->consultaRepository = $this->createMock(
            ConsultaRepositoryInterface::class
        );

        $this->reservaRepository = $this->createMock(
            ReservaRepositoryInterface::class
        );

        $this->propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $this->resenaRepository = $this->createMock(
            ResenaRepositoryInterface::class
        );

        $this->logService = $this->createMock(
            LogActividadService::class
        );

        $this->policy = new UsuarioPolicy();
    }

    private function crearServicio(): UsuarioService
    {
        return new UsuarioService(
            $this->repository,
            $this->logService,
            $this->policy,
            $this->consultaRepository,
            $this->reservaRepository,
            $this->propiedadRepository,
            $this->resenaRepository
        );
    }

    public function test_lista_usuarios_y_devuelve_el_total(): void
    {
        $usuarios = new Collection([
            new Usuario(['nombre' => 'Ana']),
            new Usuario(['nombre' => 'Luis']),
        ]);

        $this->repository
            ->expects($this->once())
            ->method('all')
            ->willReturn($usuarios);

        $resultado = $this->crearServicio()->listar();

        $this->assertSame($usuarios, $resultado['items']);
        $this->assertSame(2, $resultado['total']);
    }

    public function test_obtener_devuelve_un_usuario_existente(): void
    {
        $usuario = new Usuario(['nombre' => 'Ana']);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $resultado = $this->crearServicio()->obtener(1);

        $this->assertSame($usuario, $resultado);
    }

    public function test_obtener_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $this->repository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(ValidationException::class);

        $this->crearServicio()->obtener('abc');
    }

    public function test_obtener_lanza_excepcion_si_no_existe(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Usuario no encontrado');

        $this->crearServicio()->obtener(1);
    }

    public function test_obtener_con_rol_devuelve_un_usuario_existente(): void
    {
        $usuario = new Usuario(['nombre' => 'Ana']);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findByIdWithRole')
            ->with(1)
            ->willReturn($usuario);

        $resultado = $this->crearServicio()->obtenerConRol(1);

        $this->assertSame($usuario, $resultado);
    }

    public function test_obtener_con_rol_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $this->repository
            ->expects($this->never())
            ->method('findByIdWithRole');

        $this->expectException(ValidationException::class);

        $this->crearServicio()->obtenerConRol('abc');
    }

    public function test_obtener_con_rol_lanza_excepcion_si_no_existe(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findByIdWithRole')
            ->with(1)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Usuario no encontrado');

        $this->crearServicio()->obtenerConRol(1);
    }

    public function test_obtener_perfil_publico_devuelve_datos_y_calificaciones(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'email' => 'ana@example.com',
            'telefono' => '123456',
            'domicilio' => 'Calle privada 123',
        ]);
        $usuario->id = 5;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($usuario);

        $this->consultaRepository
            ->expects($this->once())
            ->method('existeConsultaEntreUsuarios')
            ->with(1, 5)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->never())
            ->method('existeReservaEntreUsuarios');

        $this->propiedadRepository
            ->expects($this->once())
            ->method('existePorUsuario')
            ->with(5)
            ->willReturn(true);

        $this->resenaRepository
            ->expects($this->once())
            ->method('getPromedioByUsuario')
            ->with(5)
            ->willReturn(4.567);

        $this->resenaRepository
            ->expects($this->once())
            ->method('getPromedioByPropietario')
            ->with(5)
            ->willReturn(4.876);

        $this->resenaRepository
            ->expects($this->once())
            ->method('getByUsuario')
            ->with(5)
            ->willReturn(new Collection());

        $this->resenaRepository
            ->expects($this->once())
            ->method('getByPropietario')
            ->with(5)
            ->willReturn(new Collection());

        $resultado = $this->crearServicio()
            ->obtenerPerfilPublico(5, 1, Rol::USUARIO);

        $this->assertSame(5, $resultado['id']);
        $this->assertSame('Ana', $resultado['nombre']);
        $this->assertSame('Gomez', $resultado['apellido']);

        $this->assertSame([
            'inquilino' => 4.57,
            'propiedades' => 4.88,
        ], $resultado['calificaciones']);

        $this->assertInstanceOf(
            Collection::class,
            $resultado['resenas']['inquilino']
        );

        $this->assertInstanceOf(
            Collection::class,
            $resultado['resenas']['propiedades']
        );

        $this->assertCount(0, $resultado['resenas']['inquilino']);
        $this->assertCount(0, $resultado['resenas']['propiedades']);

        $this->assertArrayNotHasKey('email', $resultado);
        $this->assertArrayNotHasKey('telefono', $resultado);
        $this->assertArrayNotHasKey('domicilio', $resultado);
        $this->assertArrayNotHasKey('contrasena', $resultado);
    }

    public function test_obtener_perfil_publico_devuelve_null_si_no_es_propietario(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
        ]);
        $usuario->id = 5;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($usuario);

        $this->consultaRepository
            ->expects($this->once())
            ->method('existeConsultaEntreUsuarios')
            ->with(1, 5)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->never())
            ->method('existeReservaEntreUsuarios');

        $this->propiedadRepository
            ->expects($this->once())
            ->method('existePorUsuario')
            ->with(5)
            ->willReturn(false);

        $this->resenaRepository
            ->expects($this->once())
            ->method('getByUsuario')
            ->with(5)
            ->willReturn(new Collection());

        $this->resenaRepository
            ->expects($this->never())
            ->method('getByPropietario');

        $this->resenaRepository
            ->expects($this->once())
            ->method('getPromedioByUsuario')
            ->with(5)
            ->willReturn(0.0);

        $this->resenaRepository
            ->expects($this->never())
            ->method('getPromedioByPropietario');

        $resultado = $this->crearServicio()
            ->obtenerPerfilPublico(5, 1, Rol::USUARIO);

        $this->assertNull(
            $resultado['calificaciones']['propiedades']
        );

        $this->assertSame(
            0.0,
            $resultado['calificaciones']['inquilino']
        );
    }

    public function test_obtener_perfil_publico_no_permite_consultar_el_propio_perfil(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
        ]);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $this->consultaRepository
            ->expects($this->never())
            ->method('existeConsultaEntreUsuarios');

        $this->reservaRepository
            ->expects($this->never())
            ->method('existeReservaEntreUsuarios');

        $this->propiedadRepository
            ->expects($this->never())
            ->method('existePorUsuario');

        $this->expectException(ForbiddenException::class);

        $this->crearServicio()->obtenerPerfilPublico(
            1,
            1,
            Rol::ADMIN
        );
    }

    public function test_actualiza_un_usuario(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'email' => 'ana@example.com',
        ]);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $this->repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('nuevo@example.com', 1)
            ->willReturn(false);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with($usuario, [
                'nombre' => 'Ana Maria',
                'email' => 'nuevo@example.com',
            ])
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(1, 'Actualización de usuario');

        $this->crearServicio()->actualizar(
            1,
            [
                'nombre' => ' ana maria ',
                'email' => ' NUEVO@EXAMPLE.COM ',
            ],
            1,
            Rol::USUARIO
        );
    }

    public function test_actualizar_encripta_la_contrasena(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
        ]);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with(
                $usuario,
                $this->callback(
                    static function (array $data): bool {
                        return isset($data['contrasena'])
                            && password_verify(
                                'secreto',
                                $data['contrasena']
                            );
                    }
                )
            )
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(1, 'Actualización de usuario');

        $this->crearServicio()->actualizar(
            1,
            ['contrasena' => 'secreto'],
            1,
            Rol::USUARIO
        );
    }

    public function test_actualizar_lanza_excepcion_si_no_hay_campos(): void
    {
        $usuario = new Usuario(['nombre' => 'Ana']);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $this->repository
            ->expects($this->never())
            ->method('update');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(BadRequestException::class);

        $this->crearServicio()->actualizar(
            1,
            [],
            1,
            Rol::USUARIO
        );
    }

    public function test_actualizar_lanza_excepcion_si_no_hay_campos_actualizables(): void
    {
        $usuario = new Usuario(['nombre' => 'Ana']);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $this->repository
            ->expects($this->never())
            ->method('update');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(BadRequestException::class);

        $this->crearServicio()->actualizar(
            1,
            ['rol_id' => 2],
            1,
            Rol::USUARIO
        );
    }

    public function test_actualizar_lanza_excepcion_si_los_datos_son_invalidos(): void
    {
        $usuario = new Usuario(['nombre' => 'Ana']);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $this->repository
            ->expects($this->never())
            ->method('existsByEmail');

        $this->repository
            ->expects($this->never())
            ->method('update');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(ValidationException::class);

        $this->crearServicio()->actualizar(
            1,
            ['nombre' => 'A'],
            1,
            Rol::USUARIO
        );
    }

    public function test_actualizar_lanza_conflicto_si_el_email_ya_existe(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
        ]);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $this->repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('otro@example.com', 1)
            ->willReturn(true);

        $this->repository
            ->expects($this->never())
            ->method('update');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('El email ya está registrado');

        $this->crearServicio()->actualizar(
            1,
            ['email' => 'otro@example.com'],
            1,
            Rol::USUARIO
        );
    }

    public function test_actualizar_no_permite_modificar_otro_usuario(): void
    {
        $usuario = new Usuario(['nombre' => 'Ana']);
        $usuario->id = 2;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($usuario);

        $this->repository
            ->expects($this->never())
            ->method('update');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $this->crearServicio()->actualizar(
            2,
            ['nombre' => 'Cambio'],
            1,
            Rol::USUARIO
        );
    }

    public function test_elimina_un_usuario_y_registra_la_actividad(): void
    {
        $usuario = new Usuario(['nombre' => 'Ana']);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($usuario);

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with($usuario)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(1, 'Eliminación de usuario');

        $this->crearServicio()->eliminar(
            1,
            1,
            Rol::USUARIO
        );
    }

    public function test_eliminar_lanza_excepcion_si_no_existe(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $this->repository
            ->expects($this->never())
            ->method('delete');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(NotFoundException::class);

        $this->crearServicio()->eliminar(
            1,
            1,
            Rol::USUARIO
        );
    }

    public function test_eliminar_no_permite_eliminar_otro_usuario(): void
    {
        $usuario = new Usuario(['nombre' => 'Ana']);
        $usuario->id = 2;

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($usuario);

        $this->repository
            ->expects($this->never())
            ->method('delete');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $this->crearServicio()->eliminar(
            2,
            1,
            Rol::USUARIO
        );
    }

    public function test_restaurar_un_usuario_eliminado_y_registra_la_actividad(): void
    {
        $usuario = new Usuario(['email' => 'ana@example.com']);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($usuario);

        $this->repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('ana@example.com')
            ->willReturn(false);

        $this->repository
            ->expects($this->once())
            ->method('restore')
            ->with($usuario)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(1, 'Restauración de usuario');

        $this->crearServicio()->restaurar(1);
    }

    public function test_restaurar_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $this->repository
            ->expects($this->never())
            ->method('findDeletedById');

        $this->repository
            ->expects($this->never())
            ->method('restore');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(ValidationException::class);

        $this->crearServicio()->restaurar('abc');
    }

    public function test_restaurar_lanza_excepcion_si_no_existe(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn(null);

        $this->repository
            ->expects($this->never())
            ->method('existsByEmail');

        $this->repository
            ->expects($this->never())
            ->method('restore');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(NotFoundException::class);

        $this->crearServicio()->restaurar(1);
    }

    public function test_restaurar_lanza_conflicto_si_el_email_ya_esta_registrado(): void
    {
        $usuario = new Usuario(['email' => 'ana@example.com']);
        $usuario->id = 1;

        $this->repository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($usuario);

        $this->repository
            ->expects($this->once())
            ->method('existsByEmail')
            ->with('ana@example.com')
            ->willReturn(true);

        $this->repository
            ->expects($this->never())
            ->method('restore');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage(
            'Ya existe un usuario activo con ese email'
        );

        $this->crearServicio()->restaurar(1);
    }
}
