<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FavoritoService;
use App\Repositories\FavoritoRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Services\LogActividadService;

class FavoritoServiceTest extends TestCase
{
    private $favoritoRepository;
    private $propiedadRepository;
    private $logService;
    private $favoritoService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->favoritoRepository = $this->createMock(FavoritoRepositoryInterface::class);
        $this->propiedadRepository = $this->createMock(PropiedadRepositoryInterface::class);
        $this->logService = $this->createMock(LogActividadService::class);

        $this->favoritoService = new FavoritoService(
            $this->favoritoRepository,
            $this->propiedadRepository,
            $this->logService
        );
    }

    /** @test */
    public function it_can_get_favoritos_by_user()
    {
        $usuarioId = 1;
        $expected = [['id' => 1, 'propiedad_id' => 1, 'usuario_id' => 1]];

        $this->favoritoRepository
            ->expects($this->once())
            ->method('getByUserId')
            ->with($usuarioId)
            ->willReturn($expected);

        $result = $this->favoritoService->obtenerFavoritos($usuarioId);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_returns_empty_array_when_usuario_id_is_zero()
    {
        $result = $this->favoritoService->obtenerFavoritos(0);
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_can_add_favorito()
    {
        $usuarioId = 1;
        $propiedadId = 2;
        $propiedadMock = (object) ['usuario_id' => 3];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with($propiedadId)
            ->willReturn($propiedadMock);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('add')
            ->with($usuarioId, $propiedadId)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar');

        $result = $this->favoritoService->agregarFavorito($usuarioId, $propiedadId);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_favorito_already_exists()
    {
        $usuarioId = 1;
        $propiedadId = 2;
        $propiedadMock = (object) ['usuario_id' => 3];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with($propiedadId)
            ->willReturn($propiedadMock);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('add')
            ->with($usuarioId, $propiedadId)
            ->willReturn(false);

        $result = $this->favoritoService->agregarFavorito($usuarioId, $propiedadId);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_throws_exception_when_property_does_not_exist()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("La propiedad no existe");
        $this->expectExceptionCode(404);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->favoritoService->agregarFavorito(1, 999);
    }

    /** @test */
    public function it_throws_exception_when_trying_to_add_own_property()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("No puedes agregar tu propia propiedad a favoritos");
        $this->expectExceptionCode(400);

        $propiedadMock = (object) ['usuario_id' => 1];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedadMock);

        $this->favoritoService->agregarFavorito(1, 1);
    }

    /** @test */
    public function it_can_remove_favorito()
    {
        $usuarioId = 1;
        $propiedadId = 2;

        $this->favoritoRepository
            ->expects($this->once())
            ->method('exists')
            ->with($usuarioId, $propiedadId)
            ->willReturn(true);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('remove')
            ->with($usuarioId, $propiedadId)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar');

        $result = $this->favoritoService->eliminarFavorito($usuarioId, $propiedadId);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_throws_exception_when_removing_non_existent_favorito()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("La propiedad no está en favoritos");
        $this->expectExceptionCode(404);

        $this->favoritoRepository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 999)
            ->willReturn(false);

        $this->favoritoService->eliminarFavorito(1, 999);
    }

    /** @test */
    public function it_can_check_if_property_is_favorito()
    {
        $this->favoritoRepository
            ->expects($this->once())
            ->method('exists')
            ->with(1, 2)
            ->willReturn(true);

        $result = $this->favoritoService->esFavorito(1, 2);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_checking_with_invalid_ids()
    {
        $result = $this->favoritoService->esFavorito(0, 2);
        $this->assertFalse($result);

        $result2 = $this->favoritoService->esFavorito(1, 0);
        $this->assertFalse($result2);
    }

    /** @test */
    public function it_can_count_favoritos_by_property()
    {
        $this->favoritoRepository
            ->expects($this->once())
            ->method('countByPropiedad')
            ->with(1)
            ->willReturn(5);

        $result = $this->favoritoService->contarFavoritos(1);
        $this->assertEquals(5, $result);
    }

    /** @test */
    public function it_returns_zero_when_counting_with_invalid_property_id()
    {
        $result = $this->favoritoService->contarFavoritos(0);
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_can_mark_favoritos_in_listado()
    {
        $usuarioId = 1;
        $propiedades = [
            ['id' => 1, 'titulo' => 'Casa 1'],
            ['id' => 2, 'titulo' => 'Casa 2'],
            ['id' => 3, 'titulo' => 'Casa 3']
        ];

        $this->favoritoRepository
            ->expects($this->once())
            ->method('getPropiedadIdsByUser')
            ->with($usuarioId)
            ->willReturn([1, 3]);

        $result = $this->favoritoService->marcarFavoritosEnListado($usuarioId, $propiedades);

        $this->assertTrue($result[0]['es_favorito']);
        $this->assertFalse($result[1]['es_favorito']);
        $this->assertTrue($result[2]['es_favorito']);
    }

    /** @test */
    public function it_returns_ids_favoritos()
    {
        $this->favoritoRepository
            ->expects($this->once())
            ->method('getPropiedadIdsByUser')
            ->with(1)
            ->willReturn([1, 2, 3]);

        $result = $this->favoritoService->obtenerIdsFavoritos(1);
        $this->assertEquals([1, 2, 3], $result);
    }

    /** @test */
    public function it_returns_empty_array_when_getting_ids_with_invalid_user()
    {
        $result = $this->favoritoService->obtenerIdsFavoritos(0);
        $this->assertEmpty($result);
    }
}