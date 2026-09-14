<?php

namespace Tests\Unit\Services;

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
            \App\Policies\FavoritoPolicy::class
        );

        $this->service = new FavoritoService(
            $this->favoritoRepository,
            $this->propiedadRepository,
            $this->logService,
            $this->policyMock
        );
    }

    public function test_obtener_favoritos_devuelve_los_favoritos_autorizados(): void
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

        $resultado = $this->service->obtenerFavoritos(
            5,
            5,
            1
        );

        $this->assertSame($favoritos, $resultado);
    }

    public function test_obtener_favoritos_lanza_excepcion_si_no_tiene_permiso(): void
    {
        $this->policyMock
            ->expects($this->once())
            ->method('puedeVerDeUsuario')
            ->with(5, 1, 8)
            ->willReturn(false);

        $this->favoritoRepository
            ->expects($this->never())
            ->method('getByUserId');

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage(
            'No tienes permiso para consultar los favoritos de este usuario'
        );

        $this->service->obtenerFavoritos(
            5,
            8,
            1
        );
    }

    public function test_obtener_favoritos_permite_a_un_administrador(): void
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

        $resultado = $this->service->obtenerFavoritos(
            5,
            8,
            2
        );

        $this->assertSame($favoritos, $resultado);
    }

    public function test_obtener_ids_favoritos_devuelve_los_ids(): void
    {
        $ids = [10, 20, 30];

        $this->favoritoRepository
            ->expects($this->once())
            ->method('getPropiedadIdsByUser')
            ->with(5)
            ->willReturn($ids);

        $resultado = $this->service->obtenerIdsFavoritos(5);

        $this->assertSame($ids, $resultado);
    }

    public function test_es_favorito_devuelve_true_si_existe(): void
    {
        $this->favoritoRepository
            ->expects($this->once())
            ->method('exists')
            ->with(5, 10)
            ->willReturn(true);

        $resultado = $this->service->esFavorito(5, 10);

        $this->assertTrue($resultado);
    }

    public function test_agregar_favorito_rechaza_propiedad_inexistente(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('La propiedad no existe');

        $this->service->agregarFavorito(5, 10);
    }

    public function test_agregar_favorito_rechaza_la_propia_propiedad(): void
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

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage(
            'No puedes agregar tu propia propiedad a favoritos'
        );

        $this->service->agregarFavorito(5, 10);
    }

    public function test_agregar_favorito_crea_el_favorito_y_registra_actividad(): void
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

        $resultado = $this->service->agregarFavorito(5, 10);

        $this->assertTrue($resultado);
    }

    public function test_eliminar_favorito_rechaza_favorito_inexistente(): void
    {
        $this->favoritoRepository
            ->expects($this->once())
            ->method('findByUsuarioAndPropiedad')
            ->with(5, 10)
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage(
            'La propiedad no está en favoritos'
        );

        $this->service->eliminarFavorito(5, 10);
    }

    public function test_eliminar_favorito_rechaza_si_policy_no_autoriza(): void
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

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage(
            'No tienes permiso para eliminar este favorito'
        );

        $this->service->eliminarFavorito(5, 10);
    }

    public function test_eliminar_favorito_elimina_y_registra_actividad(): void
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

        $resultado = $this->service->eliminarFavorito(5, 10);

        $this->assertTrue($resultado);
    }

    public function test_contar_favoritos_devuelve_la_cantidad(): void
    {
        $this->favoritoRepository
            ->expects($this->once())
            ->method('countByPropiedad')
            ->with(10)
            ->willReturn(4);

        $resultado = $this->service->contarFavoritos(10);

        $this->assertSame(4, $resultado);
    }

    public function test_marcar_favoritos_en_listado_agrega_la_marca_correctamente(): void
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

        $resultado = $this->service->marcarFavoritosEnListado(
            5,
            $propiedades
        );

        $this->assertTrue($resultado[0]['es_favorito']);
        $this->assertFalse($resultado[1]['es_favorito']);
        $this->assertTrue($resultado[2]['es_favorito']);
    }
}