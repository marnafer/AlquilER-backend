<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\FavoritoController;
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
            ->method('listar')
            ->with(5, 5, 1)
            ->willReturn($favoritos);

        $response = $this->captureJson(
            fn() => $this->controller->index()
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

        $this->service
            ->expects($this->once())
            ->method('agregar')
            ->with(5, 20)
            ->willReturn(true);

        $response = $this->captureJsonWithBody(
            '{"propiedad_id":20}',
            fn() => $this->controller->store()
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

        $this->service
            ->expects($this->never())
            ->method('agregar');

        $response = $this->captureJsonWithBody(
            '{}',
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'El campo propiedad_id es requerido y debe ser numérico',
            $response['error']
        );
    }

    public function test_store_rechaza_propiedad_id_no_numerico(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->never())
            ->method('agregar');

        $response = $this->captureJsonWithBody(
            '{"propiedad_id":"abc"}',
            fn() => $this->controller->store()
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
            ->method('listar')
            ->with(5, 8, 2)
            ->willReturn($favoritos);

        $response = $this->captureJson(
            fn() => $this->controller->indexByUsuario(8)
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
            ->method('listar');

        $response = $this->captureJson(
            fn() => $this->controller->indexByUsuario(0)
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
            ->method('eliminar')
            ->with(5, 20)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->deleteByPropiedad(20)
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
            ->method('eliminar');

        $response = $this->captureJson(
            fn() => $this->controller->deleteByPropiedad(0)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'ID de propiedad inválido',
            $response['error']
        );
    }
}