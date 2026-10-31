<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\PropiedadServicioController;
use App\Services\PropiedadServicioService;
use Tests\TestCase;

final class PropiedadServicioControllerTest extends TestCase
{
    private PropiedadServicioService $service;
    private PropiedadServicioController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(PropiedadServicioService::class);
        $this->controller = new PropiedadServicioController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );
    }

    public function test_index_devuelve_items_y_total_sin_auth(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(11)
            ->willReturn([['servicio_id' => 1], ['servicio_id' => 2]]);

        $response = $this->controller->index($this->makeRequest(), 11);

        $this->assertSame(200, $response->status());
        $this->assertCount(2, $response->payload()['data']['items']);
        $this->assertSame(2, $response->payload()['data']['total']);
    }

    public function test_get_propiedades_by_servicio(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listarPorServicio')
            ->with(4)
            ->willReturn([['propiedad_id' => 9]]);

        $response = $this->controller->getPropiedadesByServicio(
            $this->makeRequest(),
            4
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(1, $response->payload()['data']['total']);
    }

    public function test_store_asigna_un_servicio(): void
    {
        $this->service
            ->expects($this->once())
            ->method('asignar')
            ->with(11, 3, 5, 1)
            ->willReturn(['propiedad_id' => 11, 'servicio_id' => 3]);

        $response = $this->controller->store(
            $this->makeJsonRequest(['servicio_id' => 3]),
            11
        );

        $this->assertSame(201, $response->status());
        $this->assertSame(
            'Servicio asignado correctamente',
            $response->payload()['message']
        );
    }

    public function test_store_sin_servicio_id_pasa_null(): void
    {
        $this->service
            ->expects($this->once())
            ->method('asignar')
            ->with(11, null, 5, 1)
            ->willReturn([]);

        $this->controller->store($this->makeJsonRequest([]), 11);

        $this->addToAssertionCount(1);
    }

    public function test_store_multiple_asigna_varios(): void
    {
        $this->service
            ->expects($this->once())
            ->method('asignarMultiples')
            ->with(11, [1, 2, 3], 5, 1)
            ->willReturn([['ok' => true]]);

        $response = $this->controller->storeMultiple(
            $this->makeJsonRequest(['servicio_ids' => [1, 2, 3]]),
            11
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Servicios asignados correctamente',
            $response->payload()['message']
        );
    }

    public function test_store_multiple_sin_ids_pasa_arreglo_vacio(): void
    {
        $this->service
            ->expects($this->once())
            ->method('asignarMultiples')
            ->with(11, [], 5, 1)
            ->willReturn([]);

        $this->controller->storeMultiple($this->makeJsonRequest([]), 11);

        $this->addToAssertionCount(1);
    }

    public function test_update_sincroniza_los_servicios(): void
    {
        $this->service
            ->expects($this->once())
            ->method('sincronizar')
            ->with(11, [5, 6], 5, 1)
            ->willReturn([]);

        $response = $this->controller->update(
            $this->makeJsonRequest(['servicio_ids' => [5, 6]]),
            11
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Servicios sincronizados correctamente',
            $response->payload()['message']
        );
    }

    public function test_delete_desasigna_un_servicio(): void
    {
        $this->service
            ->expects($this->once())
            ->method('desasignar')
            ->with(11, 3, 5, 1);

        $response = $this->controller->delete(
            $this->makeRequest(),
            11,
            3
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Servicio desasignado correctamente',
            $response->payload()['message']
        );
    }

    public function test_store_requiere_autenticacion(): void
    {
        $controller = new PropiedadServicioController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('asignar');

        $this->expectException(\App\Exceptions\UnauthorizedException::class);

        $controller->store($this->makeJsonRequest([]), 11);
    }
}
