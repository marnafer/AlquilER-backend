<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ForbiddenException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Consulta;
use App\Models\MensajeConsulta;
use App\Models\Propiedad;
use App\Models\Usuario;
use App\Policies\ConsultaPolicy;
use App\Repositories\ConsultaRepositoryInterface;
use App\Repositories\MensajeConsultaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\ConsultaService;
use App\Services\LogActividadService;
use App\Services\NotificacionService;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ConsultaServiceTest extends TestCase
{
    private $consultaRepository;
    private $propiedadRepository;
    private $usuarioRepository;
    private $mensajeConsultaRepository;
    private $logService;
    private $policyMock;
    private $notificacionService;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consultaRepository = $this->createMock(
            ConsultaRepositoryInterface::class
        );

        $this->propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $this->usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $this->mensajeConsultaRepository = $this->createMock(
            MensajeConsultaRepositoryInterface::class
        );

        $this->logService = $this->createMock(
            LogActividadService::class
        );

        $this->policyMock = $this->createMock(
            ConsultaPolicy::class
        );

        $this->notificacionService = $this->createMock(
            NotificacionService::class
        );

        $this->service = new ConsultaService(
            $this->consultaRepository,
            $this->propiedadRepository,
            $this->usuarioRepository,
            $this->mensajeConsultaRepository,
            $this->logService,
            $this->policyMock,
            $this->notificacionService
        );

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    protected function tearDown(): void
    {
        // El Capsule con SQLite :memory: queda instalado como global. Si
        // se filtra, los tests de Integracion heredan una base sin tablas
        // y fallan con "no such table".
        Model::unsetConnectionResolver();

        parent::tearDown();
    }

    public function test_listar_devuelve_las_consultas_para_un_administrador(): void
    {
        $consultas = [
            [
                'id' => 10,
                'propiedad_id' => 20,
                'usuario_id' => 5
            ]
        ];

        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(2)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('getAll')
            ->with([])
            ->willReturn($consultas);

        $resultado = $this->service->listar(2);

        $this->assertSame($consultas, $resultado);
    }

    public function test_listar_rechaza_a_un_usuario_no_administrador(): void
    {
        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(1)
            ->willReturn(false);

        $this->consultaRepository
            ->expects($this->never())
            ->method('getAll');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No autorizado para ver el listado global de consultas'
        );

        $this->service->listar(1);
    }

    public function test_listar_envia_los_filtros_limpios_al_repository(): void
    {
        $filtros = [
            'usuario_id' => 5,
            'propiedad_id' => 20
        ];

        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(2)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('getAll')
            ->with($filtros)
            ->willReturn([]);

        $resultado = $this->service->listar(
            2,
            $filtros
        );

        $this->assertSame([], $resultado);
    }

    public function test_obtener_devuelve_la_consulta(): void
    {
        $consulta = new Consulta();
        $consulta->id = 10;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($consulta);

        $resultado = $this->service->obtener(10);

        $this->assertSame($consulta, $resultado);
    }

    public function test_obtener_rechaza_id_invalido(): void
    {
        $this->consultaRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(ValidationException::class);

        $this->service->obtener(0);
    }

    public function test_obtener_rechaza_consulta_inexistente(): void
    {
        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Consulta no encontrada'
        );

        $this->service->obtener(10);
    }

    public function test_obtenerAutorizada_devuelve_la_consulta_si_el_usuario_participa(): void
    {
        $consulta = new Consulta();
        $consulta->id = 10;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($consulta);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeParticipar')
            ->with(5, $consulta)
            ->willReturn(true);

        $resultado = $this->service->obtenerAutorizada(
            10,
            5
        );

        $this->assertSame($consulta, $resultado);
    }

    public function test_obtenerAutorizada_rechaza_usuario_no_autorizado(): void
    {
        $consulta = new Consulta();
        $consulta->id = 10;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($consulta);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeParticipar')
            ->with(5, $consulta)
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tienes permiso para acceder a esta consulta'
        );

        $this->service->obtenerAutorizada(
            10,
            5
        );
    }

    public function test_crear_crea_la_consulta_sin_mensaje(): void
    {
        $propiedad = new Propiedad();
        $propiedad->id = 20;
        $propiedad->usuario_id = 7;

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(20)
            ->willReturn($propiedad);

        $this->consultaRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                function (array $data): bool {
                    return $data['propiedad_id'] === 20
                        && $data['usuario_id'] === 5
                        && isset($data['fecha_consulta']);
                }
            ))
            ->willReturn(10);

        $this->mensajeConsultaRepository
            ->expects($this->never())
            ->method('create');

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(5, 'consulta_creada');

        $this->notificacionService
            ->expects($this->once())
            ->method('crear')
            ->with(
                7,
                'consulta_nueva',
                'Nueva consulta',
                $this->isType('string'),
                10
            );

        $resultado = $this->service->crear([
            'propiedad_id' => 20,
            'usuario_id' => 5
        ]);

        $this->assertSame(10, $resultado);
    }

    public function test_crear_crea_la_consulta_y_el_mensaje(): void
    {
        $propiedad = new Propiedad();
        $propiedad->id = 20;
        $propiedad->usuario_id = 7;

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(20)
            ->willReturn($propiedad);

        $this->consultaRepository
            ->expects($this->once())
            ->method('create')
            ->willReturn(10);

        $this->mensajeConsultaRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                function (array $data): bool {
                    return $data['consulta_id'] === 10
                        && $data['usuario_id'] === 5
                        && $data['mensaje'] === 'Estoy interesado'
                        && isset($data['fecha_mensaje']);
                }
            ));

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(5, 'consulta_creada');

        $this->notificacionService
            ->expects($this->once())
            ->method('crear')
            ->with(
                7,
                'consulta_nueva',
                'Nueva consulta',
                $this->isType('string'),
                10
            );

        $resultado = $this->service->crear([
            'propiedad_id' => 20,
            'usuario_id' => 5,
            'mensaje' => 'Estoy interesado'
        ]);

        $this->assertSame(10, $resultado);
    }

    public function test_crear_rechaza_propiedad_inexistente(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(20)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'La propiedad no existe'
        );

        $this->service->crear([
            'propiedad_id' => 20,
            'usuario_id' => 5
        ]);
    }

    public function test_actualizar_actualiza_la_consulta(): void
    {
        $consulta = new Consulta();
        $consulta->id = 10;

        $usuario = new Usuario();
        $usuario->id = 5;
        $usuario->rol_id = 1;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($consulta);

        $this->usuarioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($usuario);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeActualizar')
            ->with(5, 1, $consulta)
            ->willReturn(true);

        $mensaje = new MensajeConsulta();
        $mensaje->id = 1;

        $this->mensajeConsultaRepository
            ->expects($this->once())
            ->method('findByConsultaId')
            ->with(10)
            ->willReturn(new Collection([$mensaje]));

        $this->mensajeConsultaRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                1,
                $this->callback(
                    function (array $data): bool {
                        return ($data['mensaje'] ?? null) === 'Nuevo mensaje';
                    }
                )
            )
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(5, 'consulta_actualizada');

        $resultado = $this->service->actualizar(
            10,
            ['mensaje' => 'Nuevo mensaje'],
            5
        );

        $this->assertTrue($resultado);
    }

    public function test_actualizar_rechaza_usuario_no_autorizado(): void
    {
        $consulta = new Consulta();
        $consulta->id = 10;

        $usuario = new Usuario();
        $usuario->id = 5;
        $usuario->rol_id = 1;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($consulta);

        $this->usuarioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($usuario);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeActualizar')
            ->with(5, 1, $consulta)
            ->willReturn(false);

        $this->consultaRepository
            ->expects($this->never())
            ->method('update');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tienes permiso para actualizar esta consulta'
        );

        $this->service->actualizar(
            10,
            ['mensaje' => 'Nuevo mensaje'],
            5
        );
    }

    public function test_eliminar_elimina_la_consulta_y_registra_actividad(): void
    {
        $consulta = new Consulta();
        $consulta->id = 10;

        $usuario = new Usuario();
        $usuario->id = 5;
        $usuario->rol_id = 2;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($consulta);

        $this->usuarioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($usuario);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(2)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('delete')
            ->with(10)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(5, 'consulta_eliminada');

        $resultado = $this->service->eliminar(
            10,
            5
        );

        $this->assertTrue($resultado);
    }

    public function test_eliminar_rechaza_usuario_no_administrador(): void
    {
        $consulta = new Consulta();
        $consulta->id = 10;

        $usuario = new Usuario();
        $usuario->id = 5;
        $usuario->rol_id = 1;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($consulta);

        $this->usuarioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($usuario);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(1)
            ->willReturn(false);

        $this->consultaRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No autorizado para eliminar consultas'
        );

        $this->service->eliminar(
            10,
            5
        );
    }

    public function test_listarPorUsuario_devuelve_las_consultas_autorizadas(): void
    {
        $consultas = [
            [
                'id' => 10,
                'usuario_id' => 5
            ]
        ];

        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDeUsuario')
            ->with(5, 1, 5)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('getByUsuario')
            ->with(5)
            ->willReturn($consultas);

        $resultado = $this->service->listarPorUsuario(
            5,
            5,
            1
        );

        $this->assertSame($consultas, $resultado);
    }

    public function test_listarPorUsuario_rechaza_usuario_no_autorizado(): void
    {
        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDeUsuario')
            ->with(5, 1, 8)
            ->willReturn(false);

        $this->consultaRepository
            ->expects($this->never())
            ->method('getByUsuario');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tienes permiso para ver las consultas de este usuario'
        );

        $this->service->listarPorUsuario(
            8,
            5,
            1
        );
    }

    public function test_listarPorUsuario_rechaza_id_invalido(): void
    {
        $this->policyMock
            ->expects($this->never())
            ->method('puedeVerDeUsuario');

        $this->consultaRepository
            ->expects($this->never())
            ->method('getByUsuario');

        $this->expectException(ValidationException::class);

        $this->service->listarPorUsuario(
            0,
            5,
            1
        );
    }

    public function test_listarPorPropiedad_devuelve_las_consultas_autorizadas(): void
    {
        $propiedad = new Propiedad();
        $propiedad->id = 20;
        $propiedad->usuario_id = 5;

        $consultas = [
            [
                'id' => 10,
                'propiedad_id' => 20,
                'usuario_id' => 8
            ]
        ];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(20)
            ->willReturn($propiedad);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDePropiedad')
            ->with(5, 1, $propiedad)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('getByPropiedad')
            ->with(20)
            ->willReturn($consultas);

        $resultado = $this->service->listarPorPropiedad(
            20,
            5,
            1
        );

        $this->assertSame($consultas, $resultado);
    }

    public function test_listarPorPropiedad_rechaza_propiedad_inexistente(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(20)
            ->willReturn(null);

        $this->policyMock
            ->expects($this->never())
            ->method('puedeVerDePropiedad');

        $this->consultaRepository
            ->expects($this->never())
            ->method('getByPropiedad');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Propiedad no encontrada'
        );

        $this->service->listarPorPropiedad(
            20,
            5,
            1
        );
    }

    public function test_listarPorPropiedad_rechaza_usuario_no_autorizado(): void
    {
        $propiedad = new Propiedad();
        $propiedad->id = 20;
        $propiedad->usuario_id = 8;

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(20)
            ->willReturn($propiedad);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDePropiedad')
            ->with(5, 1, $propiedad)
            ->willReturn(false);

        $this->consultaRepository
            ->expects($this->never())
            ->method('getByPropiedad');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tienes permiso para ver las consultas de esta propiedad'
        );

        $this->service->listarPorPropiedad(
            20,
            5,
            1
        );
    }

    public function test_listar_convierte_los_flags_de_papelera_a_booleanos(): void
    {
        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(2)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('getAll')
            ->with(['solo_eliminados' => true])
            ->willReturn([]);

        $resultado = $this->service->listar(
            2,
            ['solo_eliminados' => 'true']
        );

        $this->assertSame([], $resultado);
    }

    public function test_obtenerAutorizada_permite_al_administrador(): void
    {
        $consulta = new Consulta();
        $consulta->id = 10;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($consulta);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeParticipar')
            ->with(5, $consulta)
            ->willReturn(false);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(2)
            ->willReturn(true);

        $resultado = $this->service->obtenerAutorizada(
            10,
            5,
            2
        );

        $this->assertSame($consulta, $resultado);
    }

    public function test_restaurar_restaura_consulta_eliminada_por_admin(): void
    {
        $usuario = new Usuario();
        $usuario->id = 99;
        $usuario->rol_id = 2;

        $consulta = new Consulta();
        $consulta->id = 10;
        $consulta->deleted_at = '2026-01-01 00:00:00';

        $this->usuarioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn($usuario);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(2)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($consulta);

        $this->consultaRepository
            ->expects($this->once())
            ->method('restore')
            ->with(10)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(99, 'consulta_restaurada');

        $resultado = $this->service->restaurar(
            10,
            99
        );

        $this->assertTrue($resultado);
    }

    public function test_restaurar_rechaza_a_usuario_no_administrador(): void
    {
        $usuario = new Usuario();
        $usuario->id = 99;
        $usuario->rol_id = 1;

        $this->usuarioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn($usuario);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(1)
            ->willReturn(false);

        $this->consultaRepository
            ->expects($this->never())
            ->method('restore');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No autorizado para restaurar consultas'
        );

        $this->service->restaurar(
            10,
            99
        );
    }

    public function test_restaurar_rechaza_consulta_no_eliminada(): void
    {
        $usuario = new Usuario();
        $usuario->id = 99;
        $usuario->rol_id = 2;

        $consulta = new Consulta();
        $consulta->id = 10;

        $this->usuarioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn($usuario);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(2)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($consulta);

        $this->consultaRepository
            ->expects($this->never())
            ->method('restore');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage(
            'La consulta no está eliminada'
        );

        $this->service->restaurar(
            10,
            99
        );
    }

    public function test_restaurar_rechaza_consulta_inexistente(): void
    {
        $usuario = new Usuario();
        $usuario->id = 99;
        $usuario->rol_id = 2;

        $this->usuarioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn($usuario);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(2)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn(null);

        $this->consultaRepository
            ->expects($this->never())
            ->method('restore');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Consulta no encontrada'
        );

        $this->service->restaurar(
            10,
            99
        );
    }
}