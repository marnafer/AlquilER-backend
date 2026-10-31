<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\ProvinciaController;
use App\Models\Provincia;
use App\Services\ProvinciaService;
use Tests\TestCase;

final class ProvinciaControllerTest extends TestCase
{
    private ProvinciaService $service;
    private ProvinciaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(ProvinciaService::class);
        $this->controller = new ProvinciaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 2))
        );
    }

    public function test_index_lista_las_provincias(): void
    {
        $provincias = [['id' => 1, 'nombre' => 'Buenos Aires']];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn($provincias);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame($provincias, $response->payload()['data']);
    }

    public function test_index_pasa_los_filtros_de_papelera(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([
                'solo_eliminados' => true,
                'incluir_eliminados' => true,
            ])
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => [
                'solo_eliminados' => 'yes',
                'incluir_eliminados' => '1',
            ],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_rechaza_a_un_no_administrador_en_papelera(): void
    {
        $controller = new ProvinciaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->index($this->makeRequest([
            'query' => ['solo_eliminados' => '1'],
        ]));
    }

    public function test_show_devuelve_una_provincia(): void
    {
        $provincia = new Provincia(['id' => 1, 'nombre' => 'Cordoba']);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($provincia);

        $response = $this->controller->show($this->makeRequest(), 1);

        $this->assertSame(200, $response->status());
        $this->assertSame($provincia, $response->payload()['data']);
    }

    public function test_store_crea_una_provincia(): void
    {
        $provincia = new Provincia(['id' => 9, 'nombre' => 'Mendoza']);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(['nombre' => 'Mendoza'])
            ->willReturn($provincia);

        $response = $this->controller->store($this->makeJsonRequest([
            'nombre' => 'Mendoza',
        ]));

        $this->assertSame(201, $response->status());
        $this->assertSame($provincia, $response->payload()['data']);
        $this->assertSame(
            'Provincia creada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_store_rechaza_a_un_no_administrador(): void
    {
        $controller = new ProvinciaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('crear');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->store($this->makeJsonRequest(['nombre' => 'X']));
    }

    public function test_update_actualiza_una_provincia(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(4, ['nombre' => 'Modificada']);

        $response = $this->controller->update(
            $this->makeJsonRequest(['nombre' => 'Modificada']),
            4
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Provincia actualizada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_delete_elimina_una_provincia(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(4);

        $response = $this->controller->delete($this->makeRequest(), 4);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Provincia eliminada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_restore_restaura_una_provincia(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(4);

        $response = $this->controller->restore($this->makeRequest(), 4);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Provincia restaurada exitosamente',
            $response->payload()['message']
        );
    }
}
