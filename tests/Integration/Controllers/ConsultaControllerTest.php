<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\ConsultaController;
use App\Services\ConsultaService;
use App\Models\Consulta;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ConsultaControllerTest extends TestCase
{
    private ConsultaService&MockObject $service;
    private ConsultaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(
            ConsultaService::class
        );

        $this->controller = new ConsultaController(
            $this->service
        );
    }

    public function test_adminIndex_devuelve_el_listado_global_de_consultas(): void
    {
        $this->actingAs(5, 2);

        $consultas = [
            [
                'id' => 10,
                'propiedad_id' => 20,
                'usuario_id' => 8
            ],
            [
                'id' => 11,
                'propiedad_id' => 21,
                'usuario_id' => 9
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(2, [])
            ->willReturn($consultas);

        $response = $this->captureJson(
            fn() => $this->controller->adminIndex()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            $consultas,
            $response['data']['items']
        );
        $this->assertSame(
            2,
            $response['data']['total']
        );
    }

    public function test_adminIndex_pasa_los_filtros_al_service(): void
    {
        $this->actingAs(5, 2);

        $_GET = [
            'usuario_id' => '8',
            'propiedad_id' => '20'
        ];

        $consultas = [
            [
                'id' => 10,
                'propiedad_id' => 20,
                'usuario_id' => 8
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(2, $_GET)
            ->willReturn($consultas);

        $response = $this->captureJson(
            fn() => $this->controller->adminIndex()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            $consultas,
            $response['data']['items']
        );
        $this->assertSame(
            1,
            $response['data']['total']
        );

        $_GET = [];
    }

    public function test_index_devuelve_las_consultas_del_usuario_autenticado(): void
    {
        $this->actingAs(5, 1);

        $consultas = [
            [
                'id' => 10,
                'propiedad_id' => 20,
                'usuario_id' => 5
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('listarPorUsuario')
            ->with(5, 5, 1)
            ->willReturn($consultas);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            $consultas,
            $response['data']['items']
        );
        $this->assertSame(
            1,
            $response['data']['total']
        );
    }

    public function test_indexByPropiedad_devuelve_las_consultas_de_la_propiedad(): void
    {
        $this->actingAs(5, 1);

        $consultas = [
            [
                'id' => 10,
                'propiedad_id' => 20,
                'usuario_id' => 8
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('listarPorPropiedad')
            ->with(20, 5, 1)
            ->willReturn($consultas);

        $response = $this->captureJson(
            fn() => $this->controller->indexByPropiedad(20)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            $consultas,
            $response['data']['items']
        );
        $this->assertSame(
            1,
            $response['data']['total']
        );
    }

    public function test_indexByUsuario_devuelve_las_consultas_del_usuario(): void
    {
        $this->actingAs(5, 1);

        $consultas = [
            [
                'id' => 10,
                'propiedad_id' => 20,
                'usuario_id' => 5
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('listarPorUsuario')
            ->with(5, 5, 1)
            ->willReturn($consultas);

        $response = $this->captureJson(
            fn() => $this->controller->indexByUsuario(5)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            $consultas,
            $response['data']['items']
        );
        $this->assertSame(
            1,
            $response['data']['total']
        );
    }

    public function test_show_devuelve_una_consulta_autorizada(): void
    {
        $this->actingAs(5, 1);

        $consulta = new \App\Models\Consulta();

        $this->service
            ->expects($this->once())
            ->method('obtenerAutorizada')
            ->with(10, 5)
            ->willReturn($consulta);

        $response = $this->captureJson(
            fn() => $this->controller->show(10)
        );

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
    }

    public function test_store_crea_una_consulta(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([
                'propiedad_id' => 20,
                'mensaje' => 'Estoy interesado en la propiedad',
                'usuario_id' => 5
            ])
            ->willReturn(10);

        $response = $this->captureJsonWithBody(
            json_encode([
                'propiedad_id' => 20,
                'mensaje' => 'Estoy interesado en la propiedad'
            ]),
            fn() => $this->controller->store()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            10,
            $response['data']['id']
        );
        $this->assertSame(
            'Consulta creada exitosamente',
            $response['message']
        );
    }

    public function test_update_actualiza_una_consulta(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                10,
                [
                    'mensaje' => 'Nuevo mensaje'
                ],
                5
            )
            ->willReturn(true);

        $response = $this->captureJsonWithBody(
            json_encode([
                'mensaje' => 'Nuevo mensaje'
            ]),
            fn() => $this->controller->update(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Consulta actualizada exitosamente',
            $response['message']
        );
    }

    public function test_delete_elimina_una_consulta(): void
    {
        $this->actingAs(5, 2);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(10, 5)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->delete(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Consulta eliminada exitosamente',
            $response['message']
        );
    }
}