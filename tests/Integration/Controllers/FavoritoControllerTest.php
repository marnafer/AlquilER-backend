<?php

namespace Tests\Integration\Controllers;

use App\Controllers\Api\FavoritoController;
use App\Helpers\Request;
use App\Services\FavoritoService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class FavoritoControllerTest extends TestCase
{
    private FavoritoService&MockObject $service;
    private FavoritoController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(
            FavoritoService::class
        );

        $this->controller = new FavoritoController(
            $this->service
        );
    }

    public function test_index_devuelve_los_favoritos_del_usuario_autenticado(): void
    {
        $this->actingAs(5, 1);

        $favoritos = [
            [
                'id' => 10,
                'usuario_id' => 5,
                'propiedad_id' => 20
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('obtenerFavoritos')
            ->with(5, 5, 1)
            ->willReturn($favoritos);

        ob_start();

        $this->controller->index();

        $response = json_decode(
            ob_get_clean(),
            true
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            $favoritos,
            $response['data']
        );
    }

    public function test_store_agrega_una_propiedad_a_favoritos(): void
    {
        $this->actingAs(5, 1);

        Request::setTestBody(
            json_encode([
                'propiedad_id' => 20
            ])
        );

        $this->service
            ->expects($this->once())
            ->method('agregarFavorito')
            ->with(5, 20)
            ->willReturn(true);

        ob_start();

        $this->controller->store();

        $response = json_decode(
            ob_get_clean(),
            true
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            20,
            $response['data']['propiedad_id']
        );
        $this->assertTrue(
            $response['data']['es_favorito']
        );
    }

    public function test_store_rechaza_propiedad_id_faltante(): void
    {
        $this->actingAs(5, 1);

        Request::setTestBody(
            json_encode([])
        );

        $this->service
            ->expects($this->never())
            ->method('agregarFavorito');

        ob_start();

        $this->controller->store();

        $response = json_decode(
            ob_get_clean(),
            true
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'El campo propiedad_id es requerido y debe ser numérico',
            $response['error']
        );
    }

    public function test_indexByUsuario_permite_al_administrador_consultar_otro_usuario(): void
    {
        $this->actingAs(5, 2);

        $favoritos = [
            [
                'id' => 10,
                'usuario_id' => 8,
                'propiedad_id' => 20
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('obtenerFavoritos')
            ->with(5, 8, 2)
            ->willReturn($favoritos);

        ob_start();

        $this->controller->indexByUsuario(8);

        $response = json_decode(
            ob_get_clean(),
            true
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            $favoritos,
            $response['data']
        );
    }

    public function test_indexByUsuario_rechaza_id_invalido(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->never())
            ->method('obtenerFavoritos');

        ob_start();

        $this->controller->indexByUsuario(0);

        $response = json_decode(
            ob_get_clean(),
            true
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'ID de usuario inválido',
            $response['error']
        );
    }

    public function test_deleteByPropiedad_elimina_el_favorito(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('eliminarFavorito')
            ->with(5, 20)
            ->willReturn(true);

        ob_start();

        $this->controller->deleteByPropiedad(20);

        $response = json_decode(
            ob_get_clean(),
            true
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Propiedad eliminada de favoritos',
            $response['message']
        );
    }

    public function test_deleteByPropiedad_rechaza_id_invalido(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->never())
            ->method('eliminarFavorito');

        ob_start();

        $this->controller->deleteByPropiedad(0);

        $response = json_decode(
            ob_get_clean(),
            true
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'ID de propiedad inválido',
            $response['error']
        );
    }
}