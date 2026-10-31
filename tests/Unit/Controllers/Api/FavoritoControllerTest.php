<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\FavoritoController;
use App\Services\FavoritoService;
use Tests\TestCase;

final class FavoritoControllerTest extends TestCase
{
    private FavoritoService $service;
    private FavoritoController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(FavoritoService::class);
        $this->controller = new FavoritoController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );
    }

    public function test_index_lista_los_favoritos_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(5, 5, 1)
            ->willReturn([['propiedad_id' => 9]]);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame([['propiedad_id' => 9]], $response->payload()['data']);
        $this->assertSame(
            'Favoritos obtenidos correctamente',
            $response->payload()['message']
        );
    }

    public function test_store_agrega_al_usuario_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('agregar')
            ->with(['propiedad_id' => 9], 5);

        $response = $this->controller->store($this->makeJsonRequest([
            'propiedad_id' => 9,
        ]));

        $this->assertSame(201, $response->status());
        $this->assertSame(9, $response->payload()['data']['propiedad_id']);
        $this->assertTrue($response->payload()['data']['es_favorito']);
        $this->assertSame(
            'Propiedad agregada a favoritos',
            $response->payload()['message']
        );
    }

    public function test_store_sin_propiedad_id_devuelve_null(): void
    {
        $this->service
            ->expects($this->once())
            ->method('agregar')
            ->with([], 5);

        $response = $this->controller->store($this->makeJsonRequest([]));

        $this->assertNull($response->payload()['data']['propiedad_id']);
    }

    public function test_index_by_usuario_pasa_al_solicitante_y_al_objetivo(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(5, 8, 1)
            ->willReturn([]);

        $response = $this->controller->indexByUsuario(
            $this->makeRequest(),
            8
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Favoritos del usuario obtenidos correctamente',
            $response->payload()['message']
        );
    }

    public function test_delete_by_propiedad_elimina_del_usuario_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(9, 5);

        $response = $this->controller->deleteByPropiedad(
            $this->makeRequest(),
            9
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Propiedad eliminada de favoritos',
            $response->payload()['message']
        );
    }

    public function test_requiere_autenticacion(): void
    {
        $controller = new FavoritoController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(\App\Exceptions\UnauthorizedException::class);

        $controller->index($this->makeRequest());
    }
}
