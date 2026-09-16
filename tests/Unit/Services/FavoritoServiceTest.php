<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Models\Favorito;
use App\Models\Propiedad;
use App\Policies\FavoritoPolicy;
use App\Repositories\FavoritoRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Services\FavoritoService;
use App\Services\LogActividadService;
use PHPUnit\Framework\TestCase;

class FavoritoServiceTest extends TestCase
{
    private $favoritoRepository;
    private $propiedadRepository;
    private $logService;
    private $policyMock;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->favoritoRepository = $this->createMock(
            FavoritoRepositoryInterface::class
        );

        $this->propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $this->logService = $this->createMock(
            LogActividadService::class
        );

        $this->policyMock = $this->createMock(
            FavoritoPolicy::class
        );

        $this->service = new FavoritoService(
            $this->favoritoRepository,
            $this->propiedadRepository,
            $this->logService,
            $this->policyMock
        );
    }

    public function test_listar_devuelve_los_favoritos_autorizados(): void
    {
        $favoritos = [
            [
                'id' => 10,
                'usuario_id' => 5,
                'propiedad_id' => 20
            ]
        ];

        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDeUsuario')
            ->with(5, 1, 5)
            ->willReturn(true);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('getByUserId')
            ->with(5)
            ->willReturn($favoritos);

        $resultado = $this->service->listar(
            5,
            5,
            1
        );

        $this->assertSame($favoritos, $resultado);
    }

    public function test_listar_lanza_excepcion_si_no_tiene_permiso(): void
    {
        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDeUsuario')
            ->with(5, 1, 8)
            ->willReturn(false);

        $this->favoritoRepository
            ->expects($this->never())
            ->method('getByUserId');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tienes permiso para consultar los favoritos de este usuario'
        );

        $this->service->listar(
            5,
            8,
            1
        );
    }

    public function test_listar_permite_a_un_administrador(): void
    {
        $favoritos = [
            [
                'id' => 15,
                'usuario_id' => 8,
                'propiedad_id' => 30
            ]
        ];

        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDeUsuario')
            ->with(5, 2, 8)
            ->willReturn(true);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('getByUserId')
            ->with(8)
            ->willReturn($favoritos);

        $resultado = $this->service->listar(
            5,
            8,
            2
        );

        $this->assertSame($favoritos, $resultado);
    }

    public function test_listar_devuelve_vacio_si_el_usuario_es_invalido(): void
    {
        $this->policyMock
            ->expects($this->never())
            ->method('puedeVerDeUsuario');

        $this->favoritoRepository
            ->expects($this->never())
            ->method('getByUserId');

        $resultado = $this->service->listar(
            0,
            5,
            1
        );

        $this->assertSame([], $resultado);
    }

    public function test_listarIds_devuelve_los_ids(): void
    {
        $ids = [10, 20, 30];

        $this->favoritoRepository
            ->expects($this->once())
            ->method('getPropiedadIdsByUser')
            ->with(5)
            ->willReturn($ids);

        $resultado = $this->service->listarIds(5);

        $this->assertSame($ids, $resultado);
    }

    public function test_listarIds_devuelve_vacio_si_el_usuario_es_invalido(): void
    {
        $this->favoritoRepository
            ->expects($this->never())
            ->method('getPropiedadIdsByUser');

        $resultado = $this->service->listarIds(0);

        $this->assertSame([], $resultado);
    }

    public function test_verificar_devuelve_true_si_existe(): void
    {
        $this->favoritoRepository
            ->expects($this->once())
            ->method('exists')
            ->with(5, 10)
            ->willReturn(true);

        $resultado = $this->service->verificar(5, 10);

        $this->assertTrue($resultado);
    }

    public function test_verificar_devuelve_false_si_no_existe(): void
    {
        $this->favoritoRepository
            ->expects($this->once())
            ->method('exists')
            ->with(5, 10)
            ->willReturn(false);

        $resultado = $this->service->verificar(5, 10);

        $this->assertFalse($resultado);
    }

    public function test_verificar_devuelve_false_si_los_ids_son_invalidos(): void
    {
        $this->favoritoRepository
            ->expects($this->never())
            ->method('exists');

        $resultado = $this->service->verificar(0, 10);

        $this->assertFalse($resultado);
    }

    public function test_agregar_rechaza_usuario_invalido(): void
    {
        $this->propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('ID de usuario inválido');

        $this->service->agregar(0, 10);
    }

    public function test_agregar_rechaza_propiedad_invalida(): void
    {
        $this->propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('ID de propiedad inválido');

        $this->service->agregar(5, 0);
    }

    public function test_agregar_rechaza_propiedad_inexistente(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('La propiedad no existe');

        $this->service->agregar(5, 10);
    }

    public function test_agregar_rechaza_la_propia_propiedad(): void
    {
        $propiedad = new Propiedad();
        $propiedad->id = 10;
        $propiedad->usuario_id = 5;

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($propiedad);

        $this->favoritoRepository
            ->expects($this->never())
            ->method('add');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'No puedes agregar tu propia propiedad a favoritos'
        );

        $this->service->agregar(5, 10);
    }

    public function test_agregar_rechaza_favorito_duplicado(): void
    {
        $propiedad = new Propiedad();
        $propiedad->id = 10;
        $propiedad->usuario_id = 8;

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($propiedad);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('add')
            ->with(5, 10)
            ->willReturn(false);

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage(
            'La propiedad ya está en favoritos'
        );

        $this->service->agregar(5, 10);
    }

    public function test_agregar_crea_el_favorito_y_registra_actividad(): void
    {
        $propiedad = new Propiedad();
        $propiedad->id = 10;
        $propiedad->usuario_id = 8;

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($propiedad);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('add')
            ->with(5, 10)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(5, 'favorito_agregado');

        $resultado = $this->service->agregar(5, 10);

        $this->assertTrue($resultado);
    }

    public function test_eliminar_rechaza_usuario_invalido(): void
    {
        $this->favoritoRepository
            ->expects($this->never())
            ->method('findByUsuarioAndPropiedad');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('ID de usuario inválido');

        $this->service->eliminar(0, 10);
    }

    public function test_eliminar_rechaza_propiedad_invalida(): void
    {
        $this->favoritoRepository
            ->expects($this->never())
            ->method('findByUsuarioAndPropiedad');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('ID de propiedad inválido');

        $this->service->eliminar(5, 0);
    }

    public function test_eliminar_rechaza_favorito_inexistente(): void
    {
        $this->favoritoRepository
            ->expects($this->once())
            ->method('findByUsuarioAndPropiedad')
            ->with(5, 10)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'La propiedad no está en favoritos'
        );

        $this->service->eliminar(5, 10);
    }

    public function test_eliminar_rechaza_si_policy_no_autoriza(): void
    {
        $favorito = new Favorito();
        $favorito->usuario_id = 8;
        $favorito->propiedad_id = 10;

        $this->favoritoRepository
            ->expects($this->once())
            ->method('findByUsuarioAndPropiedad')
            ->with(5, 10)
            ->willReturn($favorito);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeEliminar')
            ->with(5, $favorito)
            ->willReturn(false);

        $this->favoritoRepository
            ->expects($this->never())
            ->method('remove');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tienes permiso para eliminar este favorito'
        );

        $this->service->eliminar(5, 10);
    }

    public function test_eliminar_rechaza_si_el_repository_no_elimina(): void
    {
        $favorito = new Favorito();
        $favorito->usuario_id = 5;
        $favorito->propiedad_id = 10;

        $this->favoritoRepository
            ->expects($this->once())
            ->method('findByUsuarioAndPropiedad')
            ->with(5, 10)
            ->willReturn($favorito);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeEliminar')
            ->with(5, $favorito)
            ->willReturn(true);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('remove')
            ->with(5, 10)
            ->willReturn(false);

        $this->logService
            ->expects($this->never())
            ->method('registrar');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'No se pudo eliminar el favorito'
        );

        $this->service->eliminar(5, 10);
    }

    public function test_eliminar_elimina_y_registra_actividad(): void
    {
        $favorito = new Favorito();
        $favorito->usuario_id = 5;
        $favorito->propiedad_id = 10;

        $this->favoritoRepository
            ->expects($this->once())
            ->method('findByUsuarioAndPropiedad')
            ->with(5, 10)
            ->willReturn($favorito);

        $this->policyMock
            ->expects($this->once())
            ->method('puedeEliminar')
            ->with(5, $favorito)
            ->willReturn(true);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('remove')
            ->with(5, 10)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(5, 'favorito_eliminado');

        $resultado = $this->service->eliminar(5, 10);

        $this->assertTrue($resultado);
    }

    public function test_contar_devuelve_la_cantidad(): void
    {
        $this->favoritoRepository
            ->expects($this->once())
            ->method('countByPropiedad')
            ->with(10)
            ->willReturn(4);

        $resultado = $this->service->contar(10);

        $this->assertSame(4, $resultado);
    }

    public function test_contar_devuelve_cero_si_la_propiedad_es_invalida(): void
    {
        $this->favoritoRepository
            ->expects($this->never())
            ->method('countByPropiedad');

        $resultado = $this->service->contar(0);

        $this->assertSame(0, $resultado);
    }

    public function test_marcarEnListado_agrega_la_marca_correctamente(): void
    {
        $propiedades = [
            ['id' => 10, 'titulo' => 'Casa'],
            ['id' => 20, 'titulo' => 'Departamento'],
            ['id' => 30, 'titulo' => 'Local']
        ];

        $this->favoritoRepository
            ->expects($this->once())
            ->method('getPropiedadIdsByUser')
            ->with(5)
            ->willReturn([10, 30]);

        $resultado = $this->service->marcarEnListado(
            5,
            $propiedades
        );

        $this->assertTrue($resultado[0]['es_favorito']);
        $this->assertFalse($resultado[1]['es_favorito']);
        $this->assertTrue($resultado[2]['es_favorito']);
    }

    public function test_marcarEnListado_devuelve_las_propiedades_si_el_usuario_es_invalido(): void
    {
        $propiedades = [
            ['id' => 10, 'titulo' => 'Casa']
        ];

        $this->favoritoRepository
            ->expects($this->never())
            ->method('getPropiedadIdsByUser');

        $resultado = $this->service->marcarEnListado(
            0,
            $propiedades
        );

        $this->assertSame($propiedades, $resultado);
    }
}