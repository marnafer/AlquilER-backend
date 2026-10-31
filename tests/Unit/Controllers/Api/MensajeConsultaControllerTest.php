<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\MensajeConsultaController;
use App\Services\MensajeConsultaService;
use Tests\TestCase;

final class MensajeConsultaControllerTest extends TestCase
{
    private MensajeConsultaService $service;
    private MensajeConsultaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(MensajeConsultaService::class);
        $this->controller = new MensajeConsultaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );
    }

    public function test_index_devuelve_el_historial(): void
    {
        $resultado = [
            'items' => [['id' => 1, 'texto' => 'Hola']],
            'total' => 1,
        ];

        $this->service
            ->expects($this->once())
            ->method('obtenerHistorial')
            ->with(3, 5, 1, null, null, null)
            ->willReturn($resultado);

        $response = $this->controller->index($this->makeRequest(), 3);

        $this->assertSame(200, $response->status());
        $this->assertSame($resultado, $response->payload()['data']);
    }

    public function test_index_pasa_el_paginado_desde_la_query(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtenerHistorial')
            ->with(3, 5, 1, '10', '20', '5')
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => [
                'antes_de_id' => '10',
                'despues_de_id' => '20',
                'limite' => '5',
            ],
        ]), 3);

        $this->addToAssertionCount(1);
    }

    public function test_index_requiere_autenticacion(): void
    {
        $controller = new MensajeConsultaController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('obtenerHistorial');

        $this->expectException(\App\Exceptions\UnauthorizedException::class);

        $controller->index($this->makeRequest(), 3);
    }

    public function test_store_agrega_la_consulta_y_el_emisor(): void
    {
        $mensaje = new \App\Models\MensajeConsulta(['id' => 11]);

        $this->service
            ->expects($this->once())
            ->method('crearMensaje')
            ->with(
                ['texto' => 'Hola', 'consulta_id' => 3],
                5,
                1
            )
            ->willReturn($mensaje);

        $response = $this->controller->store(
            $this->makeJsonRequest(['texto' => 'Hola']),
            3
        );

        $this->assertSame(201, $response->status());
        $this->assertSame($mensaje, $response->payload()['data']);
        $this->assertSame(
            'Mensaje enviado correctamente',
            $response->payload()['message']
        );
    }

    public function test_store_requiere_autenticacion(): void
    {
        $controller = new MensajeConsultaController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('crearMensaje');

        $this->expectException(\App\Exceptions\UnauthorizedException::class);

        $controller->store($this->makeJsonRequest(['texto' => 'x']), 3);
    }
}
