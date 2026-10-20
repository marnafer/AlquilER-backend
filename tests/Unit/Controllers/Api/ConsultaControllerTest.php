<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\ConsultaController;
use App\Services\ConsultaService;
use Tests\TestCase;

final class ConsultaControllerTest extends TestCase
{
    private ConsultaService $service;
    private ConsultaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(ConsultaService::class);
        $this->controller = new ConsultaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 2))
        );
    }

    public function test_admin_index_devuelve_items_y_total(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(2, [])
            ->willReturn([['id' => 1]]);

        $response = $this->controller->adminIndex($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame([['id' => 1]], $response->payload()['data']['items']);
        $this->assertSame(1, $response->payload()['data']['total']);
    }

    public function test_admin_index_pasa_los_filtros(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(2, ['estado' => 'pendiente'])
            ->willReturn([]);

        $this->controller->adminIndex($this->makeRequest([
            'query' => ['estado' => 'pendiente'],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_lista_las_del_usuario_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listarPorUsuario')
            ->with(5, 5, 2)
            ->willReturn([['id' => 1], ['id' => 2]]);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame(2, $response->payload()['data']['total']);
    }

    public function test_index_by_propiedad_pasa_los_permisos(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listarPorPropiedad')
            ->with(7, 5, 2)
            ->willReturn([]);

        $response = $this->controller->indexByPropiedad(
            $this->makeRequest(),
            7
        );

        $this->assertSame(200, $response->status());
    }

    public function test_index_by_usuario_pasa_el_solicitante_y_el_objetivo(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listarPorUsuario')
            ->with(9, 5, 2)
            ->willReturn([]);

        $response = $this->controller->indexByUsuario(
            $this->makeRequest(),
            9
        );

        $this->assertSame(200, $response->status());
    }

    public function test_index_recibidas_usa_al_propietario(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listarRecibidasPorPropietario')
            ->with(5)
            ->willReturn([['id' => 1]]);

        $response = $this->controller->indexRecibidas($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame(1, $response->payload()['data']['total']);
    }

    public function test_show_pasa_los_permisos_del_solicitante(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtenerAutorizada')
            ->with(3, 5, 2)
            ->willReturn(new \App\Models\Consulta(['id' => 3]));

        $response = $this->controller->show($this->makeRequest(), 3);

        $this->assertSame(200, $response->status());
    }

    public function test_store_inyecta_al_usuario_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(['propiedad_id' => 7, 'mensaje' => 'Hola', 'usuario_id' => 5])
            ->willReturn(44);

        $response = $this->controller->store($this->makeJsonRequest([
            'propiedad_id' => 7,
            'mensaje' => 'Hola',
        ]));

        $this->assertSame(201, $response->status());
        $this->assertSame(['id' => 44], $response->payload()['data']);
        $this->assertSame(
            'Consulta creada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_update_pasa_al_usuario_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(3, ['mensaje' => 'Editado'], 5);

        $response = $this->controller->update(
            $this->makeJsonRequest(['mensaje' => 'Editado']),
            3
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Consulta actualizada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_delete_pasa_al_usuario_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(3, 5);

        $response = $this->controller->delete($this->makeRequest(), 3);

        $this->assertSame(200, $response->status());
    }

    public function test_restore_pasa_al_usuario_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(3, 5);

        $response = $this->controller->restore($this->makeRequest(), 3);

        $this->assertSame(200, $response->status());
    }

    public function test_requiere_autenticacion(): void
    {
        $controller = new ConsultaController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('listarPorUsuario');

        $this->expectException(\App\Exceptions\UnauthorizedException::class);

        $controller->index($this->makeRequest());
    }
}
