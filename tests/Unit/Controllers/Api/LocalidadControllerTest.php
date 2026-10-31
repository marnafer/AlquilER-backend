<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\LocalidadController;
use App\Models\Localidad;
use App\Services\LocalidadService;
use Tests\TestCase;

final class LocalidadControllerTest extends TestCase
{
    private LocalidadService $service;
    private LocalidadController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(LocalidadService::class);
        $this->controller = new LocalidadController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 2))
        );
    }

    public function test_index_lista_las_localidades(): void
    {
        $localidades = [['id' => 1, 'nombre' => 'La Plata']];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn($localidades);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame($localidades, $response->payload()['data']);
    }

    public function test_index_pasa_los_filtros_de_papelera(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(['solo_eliminados' => true])
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => ['solo_eliminados' => '1'],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_pasa_el_filtro_incluir_eliminados(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(['incluir_eliminados' => false])
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => ['incluir_eliminados' => '0'],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_rechaza_a_un_no_administrador_en_papelera(): void
    {
        $controller = new LocalidadController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->index($this->makeRequest([
            'query' => ['solo_eliminados' => '1'],
        ]));
    }

    public function test_show_devuelve_una_localidad(): void
    {
        $localidad = new Localidad(['id' => 1, 'nombre' => 'La Plata']);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($localidad);

        $response = $this->controller->show($this->makeRequest(), 1);

        $this->assertSame(200, $response->status());
        $this->assertSame($localidad, $response->payload()['data']);
    }

    public function test_store_crea_una_localidad(): void
    {
        $localidad = new Localidad(['id' => 8, 'nombre' => 'Mar del Plata']);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(['nombre' => 'Mar del Plata', 'provincia_id' => 1])
            ->willReturn($localidad);

        $response = $this->controller->store($this->makeJsonRequest([
            'nombre' => 'Mar del Plata',
            'provincia_id' => 1,
        ]));

        $this->assertSame(201, $response->status());
        $this->assertSame($localidad, $response->payload()['data']);
        $this->assertSame(
            'Localidad creada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_store_rechaza_a_un_no_administrador(): void
    {
        $controller = new LocalidadController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('crear');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->store($this->makeJsonRequest(['nombre' => 'X']));
    }

    public function test_update_actualiza_una_localidad(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(7, ['nombre' => 'Modificada']);

        $response = $this->controller->update(
            $this->makeJsonRequest(['nombre' => 'Modificada']),
            7
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Localidad actualizada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_delete_elimina_una_localidad(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(7);

        $response = $this->controller->delete($this->makeRequest(), 7);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Localidad eliminada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_restore_restaura_una_localidad(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(7);

        $response = $this->controller->restore($this->makeRequest(), 7);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Localidad restaurada exitosamente',
            $response->payload()['message']
        );
    }
}
