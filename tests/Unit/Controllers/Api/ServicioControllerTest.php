<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\ServicioController;
use App\Models\Servicio;
use App\Services\ServicioService;
use Tests\TestCase;

final class ServicioControllerTest extends TestCase
{
    private ServicioService $service;
    private ServicioController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(ServicioService::class);
        $this->controller = new ServicioController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 2))
        );
    }

    public function test_index_lista_los_servicios(): void
    {
        $servicios = [['id' => 1, 'nombre' => 'WiFi']];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn($servicios);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame($servicios, $response->payload()['data']);
    }

    public function test_index_pasa_los_filtros_de_papelera(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(['incluir_eliminados' => true])
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => ['incluir_eliminados' => 'true'],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_rechaza_a_un_no_administrador_en_papelera(): void
    {
        $controller = new ServicioController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->index($this->makeRequest([
            'query' => ['incluir_eliminados' => 'true'],
        ]));
    }

    public function test_show_devuelve_un_servicio(): void
    {
        $servicio = new Servicio(['id' => 1, 'nombre' => 'WiFi']);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($servicio);

        $response = $this->controller->show($this->makeRequest(), 1);

        $this->assertSame(200, $response->status());
        $this->assertSame($servicio, $response->payload()['data']);
    }

    public function test_store_crea_un_servicio(): void
    {
        $servicio = new Servicio(['id' => 4, 'nombre' => 'Pileta']);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(['nombre' => 'Pileta'])
            ->willReturn($servicio);

        $response = $this->controller->store($this->makeJsonRequest([
            'nombre' => 'Pileta',
        ]));

        $this->assertSame(201, $response->status());
        $this->assertSame($servicio, $response->payload()['data']);
        $this->assertSame(
            'Servicio creado exitosamente',
            $response->payload()['message']
        );
    }

    public function test_store_rechaza_a_un_no_administrador(): void
    {
        $controller = new ServicioController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('crear');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->store($this->makeJsonRequest(['nombre' => 'X']));
    }

    public function test_update_actualiza_un_servicio(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(2, ['nombre' => 'Modificado']);

        $response = $this->controller->update(
            $this->makeJsonRequest(['nombre' => 'Modificado']),
            2
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Servicio actualizado exitosamente',
            $response->payload()['message']
        );
    }

    public function test_delete_elimina_un_servicio(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(2);

        $response = $this->controller->delete($this->makeRequest(), 2);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Servicio eliminado exitosamente',
            $response->payload()['message']
        );
    }

    public function test_restore_restaura_un_servicio(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(2);

        $response = $this->controller->restore($this->makeRequest(), 2);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Servicio restaurado exitosamente',
            $response->payload()['message']
        );
    }
}
