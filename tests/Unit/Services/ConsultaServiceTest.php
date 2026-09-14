<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ConsultaService;
use App\Repositories\ConsultaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Repositories\MensajeConsultaRepositoryInterface;
use App\Services\LogActividadService;
use App\Models\Propiedad;
use App\Models\Consulta;
use App\Models\Usuario;
use App\Exceptions\ValidationException;
use App\Exceptions\UnauthorizedException;

class ConsultaServiceTest extends TestCase
{
    private $consultaRepository;
    private $propiedadRepository;
    private $usuarioRepository;
    private $mensajeConsultaRepository;
    private $logService;
    private $consultaService;
    private $policyMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Inicializamos SQLite en memoria para las transacciones.
        $capsule = new \Illuminate\Database\Capsule\Manager;

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Tabla mínima necesaria para que DB::transaction() funcione.
        \Illuminate\Database\Capsule\Manager::schema()->create(
            'propiedades',
            function ($table) {
                $table->increments('id');
                $table->timestamps();
            }
        );

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
            \App\Policies\ConsultaPolicy::class
        );

        $this->consultaService = new ConsultaService(
            $this->consultaRepository,
            $this->propiedadRepository,
            $this->usuarioRepository,
            $this->mensajeConsultaRepository,
            $this->logService,
            $this->policyMock
        );
    }

    // =========================================================
    // listarConsultas()
    // =========================================================

    
    public function test_el_administrador_puede_listar_todas_las_consultas(): void
    {
        $filtros = ['usuario_id' => 1];

        $expected = [
            ['id' => 1, 'propiedad_id' => 1]
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
            ->willReturn($expected);

        $result = $this->consultaService->listarConsultas(
            2,
            $filtros
        );

        $this->assertEquals($expected, $result);
    }

    
    public function test_un_usuario_no_administrador_no_puede_listar_todas_las_consultas(): void
    {
        $this->policyMock
            ->expects($this->once())
            ->method('puedeAdministrar')
            ->with(1)
            ->willReturn(false);

        $this->consultaRepository
            ->expects($this->never())
            ->method('getAll');

        $this->expectException(UnauthorizedException::class);

        $this->consultaService->listarConsultas(1, []);
    }

    // =========================================================
    // obtenerConsulta()
    // =========================================================

    
    public function test_se_puede_obtener_una_consulta_por_id(): void
    {
        $consulta = new Consulta([
            'id' => 1
        ]);

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($consulta);

        $result = $this->consultaService->obtenerConsulta(1);

        $this->assertSame($consulta, $result);
    }

    
    public function test_lanza_excepcion_si_la_consulta_no_existe(): void
    {
        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(ValidationException::class);

        $this->consultaService->obtenerConsulta(999);
    }

    
    public function test_lanza_excepcion_si_el_id_de_consulta_es_invalido(): void
    {
        $this->consultaRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(ValidationException::class);

        $this->consultaService->obtenerConsulta(0);
    }

    // =========================================================
    // obtenerConsultaAutorizada()
    // =========================================================

    
    public function test_un_usuario_autorizado_puede_obtener_una_consulta(): void
    {
        $consulta = new Consulta([
            'id' => 1
        ]);

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($consulta);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeParticipar')
            ->with(5, $consulta)
            ->willReturn(true);

        $result = $this->consultaService->obtenerConsultaAutorizada(
            1,
            5
        );

        $this->assertSame($consulta, $result);
    }

    
    public function test_un_usuario_no_autorizado_no_puede_obtener_una_consulta(): void
    {
        $consulta = new Consulta([
            'id' => 1
        ]);

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($consulta);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeParticipar')
            ->with(5, $consulta)
            ->willReturn(false);

        $this->expectException(UnauthorizedException::class);

        $this->consultaService->obtenerConsultaAutorizada(
            1,
            5
        );
    }

    // =========================================================
    // crearConsulta()
    // =========================================================

    
    public function test_no_se_puede_crear_una_consulta_sin_mensaje_inicial(): void
    {
        $this->propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $this->consultaRepository
            ->expects($this->never())
            ->method('create');

        $this->mensajeConsultaRepository
            ->expects($this->never())
            ->method('create');

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(ValidationException::class);

        $this->consultaService->crearConsulta([
            'propiedad_id' => 1,
            'usuario_id' => 2
        ]);
    }

    
    public function test_lanza_excepcion_cuando_la_propiedad_no_existe_al_crear(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->consultaRepository
            ->expects($this->never())
            ->method('create');

        $this->expectException(ValidationException::class);

        $this->consultaService->crearConsulta([
            'propiedad_id' => 999,
            'usuario_id' => 1,
            'mensaje' => 'Hola, este mensaje es válido'
        ]);
    }

    // =========================================================
    // actualizarConsulta()
    // =========================================================

    
    public function test_se_puede_actualizar_una_consulta(): void
    {
        $consulta = new Consulta([
            'id' => 1
        ]);

        $usuario = new Usuario();
        $usuario->id = 5;
        $usuario->rol_id = 2;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($consulta);

        $this->usuarioRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($usuario);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeActualizar')
            ->with(5, 2, $consulta)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                1,
                $this->callback(function (array $data) {
                    return $data['id'] === 1
                        && $data['mensaje'] === 'Mensaje actualizado';
                })
            )
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(5, 'consulta_actualizada');

        $result = $this->consultaService->actualizarConsulta(
            1,
            ['mensaje' => 'Mensaje actualizado'],
            5
        );

        $this->assertTrue($result);
    }

    
    public function test_no_se_puede_actualizar_una_consulta_sin_autorizacion(): void
    {
        $consulta = new Consulta([
            'id' => 1
        ]);

        $usuario = new Usuario();
        $usuario->id = 5;
        $usuario->rol_id = 1;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
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

        $this->expectException(UnauthorizedException::class);

        $this->consultaService->actualizarConsulta(
            1,
            ['mensaje' => 'Mensaje actualizado'],
            5
        );
    }

    // =========================================================
    // eliminarConsulta()
    // =========================================================

    
    public function test_un_administrador_puede_eliminar_una_consulta(): void
    {
        $consulta = new Consulta([
            'id' => 1
        ]);

        $usuario = new Usuario();
        $usuario->id = 5;
        $usuario->rol_id = 2;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
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
            ->with(1)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(5, 'consulta_eliminada');

        $result = $this->consultaService->eliminarConsulta(
            1,
            5
        );

        $this->assertTrue($result);
    }

    
    public function test_un_usuario_no_administrador_no_puede_eliminar_una_consulta(): void
    {
        $consulta = new Consulta([
            'id' => 1
        ]);

        $usuario = new Usuario();
        $usuario->id = 5;
        $usuario->rol_id = 1;

        $this->consultaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
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

        $this->expectException(UnauthorizedException::class);

        $this->consultaService->eliminarConsulta(
            1,
            5
        );
    }

    // =========================================================
    // restaurarConsulta()
    // =========================================================

    
    public function test_un_administrador_puede_restaurar_una_consulta(): void
    {
        $usuario = new Usuario();
        $usuario->id = 5;
        $usuario->rol_id = 2;

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
            ->method('restore')
            ->with(1)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(5, 'consulta_restaurada');

        $result = $this->consultaService->restaurarConsulta(
            1,
            5
        );

        $this->assertTrue($result);
    }

    
    public function test_un_usuario_no_administrador_no_puede_restaurar_una_consulta(): void
    {
       $usuario = new Usuario();
        $usuario->id = 5;
        $usuario->rol_id = 1;

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
            ->method('restore');

        $this->expectException(UnauthorizedException::class);

        $this->consultaService->restaurarConsulta(
            1,
            5
        );
    }

    
    public function test_lanza_excepcion_si_no_se_puede_restaurar_la_consulta(): void
    {
        $usuario = new Usuario();
        $usuario->id = 5;
        $usuario->rol_id = 2;

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
            ->method('restore')
            ->with(1)
            ->willReturn(false);

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(ValidationException::class);

        $this->consultaService->restaurarConsulta(
            1,
            5
        );
    }

    // =========================================================
    // obtenerConsultasPorUsuario()
    // =========================================================

    
    public function test_se_pueden_obtener_las_consultas_por_usuario(): void
    {
        $expected = [
            ['id' => 1]
        ];

        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDeUsuario')
            ->with(1, 1, 1)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('getByUsuario')
            ->with(1)
            ->willReturn($expected);

        $result = $this->consultaService->obtenerConsultasPorUsuario(
            1,
            1,
            1
        );

        $this->assertEquals($expected, $result);
    }

    
    public function test_lanza_excepcion_si_no_esta_autorizado_para_ver_consultas_de_usuario(): void
    {
        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDeUsuario')
            ->with(1, 1, 2)
            ->willReturn(false);

        $this->consultaRepository
            ->expects($this->never())
            ->method('getByUsuario');

        $this->expectException(UnauthorizedException::class);

        $this->consultaService->obtenerConsultasPorUsuario(
            2,
            1,
            1
        );
    }

    // =========================================================
    // obtenerConsultasPorPropiedad()
    // =========================================================

    
    public function test_se_pueden_obtener_las_consultas_por_propiedad(): void
    {
        $expected = [
            ['id' => 1]
        ];

        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 1
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDePropiedad')
            ->with(1, 1, $propiedad)
            ->willReturn(true);

        $this->consultaRepository
            ->expects($this->once())
            ->method('getByPropiedad')
            ->with(1)
            ->willReturn($expected);

        $result = $this->consultaService->obtenerConsultasPorPropiedad(
            1,
            1,
            1
        );

        $this->assertEquals($expected, $result);
    }

    
    public function test_lanza_excepcion_si_la_propiedad_no_existe(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->policyMock
            ->expects($this->never())
            ->method('puedeVerDePropiedad');

        $this->consultaRepository
            ->expects($this->never())
            ->method('getByPropiedad');

        $this->expectException(ValidationException::class);

        $this->consultaService->obtenerConsultasPorPropiedad(
            999,
            1,
            1
        );
    }
}