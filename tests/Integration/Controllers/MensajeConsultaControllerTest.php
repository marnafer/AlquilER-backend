<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Controllers\Api\MensajeConsultaController;
use App\Models\MensajeConsulta;
use App\Services\MensajeConsultaService;
use Tests\TestCase;

final class MensajeConsultaControllerTest extends TestCase
{
    private $service;
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Simulamos que el usuario 5 está logueado y autenticado mediante el middleware
        $this->actingAs(5, 1);

        $this->service = $this->createMock(MensajeConsultaService::class);
        $this->controller = new MensajeConsultaController($this->service);
    }

    public function test_index_retorna_200_con_items_y_total_al_tener_permiso(): void
    {
        $mensajesFalsos = [
            new MensajeConsulta(['id' => 1, 'mensaje' => 'Hola']),
            new MensajeConsulta(['id' => 2, 'mensaje' => '¿Sigue disponible?'])
        ];

        $this->service->expects($this->once())
            ->method('obtenerHistorial')
            ->with(100, 5)
            ->willReturn(new \Illuminate\Database\Eloquent\Collection($mensajesFalsos));

        $response = $this->captureJson(
            fn() => $this->controller->index(100)
        );

        $this->assertEquals(200, http_response_code());
        $this->assertTrue($response['success']);
        $this->assertSame(2, $response['data']['total']);
        $this->assertCount(2, $response['data']['items']);
    }

    public function test_store_retorna_201_y_crea_el_mensaje_correctamente(): void
    {
        $input = json_encode(['mensaje' => 'Me interesa']);

        $mensajeCreado = new MensajeConsulta([
            'id' => 1,
            'consulta_id' => 100,
            'usuario_id' => 5,
            'mensaje' => 'Me interesa'
        ]);

        $this->service->expects($this->once())
            ->method('crearMensaje')
            ->with(
                $this->callback(function (array $data): bool {
                    return $data['consulta_id'] === 100
                        && $data['mensaje'] === 'Me interesa';
                }),
                5
            )
            ->willReturn($mensajeCreado);

        $response = $this->captureJsonWithBody(
            $input,
            fn() => $this->controller->store(100)
        );

        $this->assertEquals(201, http_response_code());
        $this->assertTrue($response['success']);
        $this->assertSame('Me interesa', $response['data']['mensaje']);
    }
}