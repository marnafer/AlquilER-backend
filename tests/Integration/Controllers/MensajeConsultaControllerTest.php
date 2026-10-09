<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

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
        $_GET = [];

        $mensajesFalsos = new \Illuminate\Database\Eloquent\Collection([
            new MensajeConsulta(['id' => 1, 'mensaje' => 'Hola']),
            new MensajeConsulta(['id' => 2, 'mensaje' => '¿Sigue disponible?'])
        ]);

        $this->service->expects($this->once())
            ->method('obtenerHistorial')
            ->with(100, 5, 1, null, null, null)
            ->willReturn([
                'items' => $mensajesFalsos,
                'total' => 2,
                'hay_anteriores' => false,
                'hay_mas_nuevos' => false
            ]);

        $response = $this->captureJson(
            fn() => $this->controller->index(100)
        );

        $this->assertEquals(200, http_response_code());
        $this->assertTrue($response['success']);
        $this->assertSame(2, $response['data']['total']);
        $this->assertCount(2, $response['data']['items']);
        $this->assertFalse($response['data']['hay_anteriores']);
        $this->assertFalse($response['data']['hay_mas_nuevos']);
    }

    public function test_index_pasa_los_parametros_de_paginacion_al_servicio(): void
    {
        $_GET = [
            'antes_de_id' => '3',
            'limite' => '2'
        ];

        $this->service->expects($this->once())
            ->method('obtenerHistorial')
            ->with(100, 5, 1, '3', null, '2')
            ->willReturn([
                'items' => new \Illuminate\Database\Eloquent\Collection(),
                'total' => 0,
                'hay_anteriores' => false,
                'hay_mas_nuevos' => false
            ]);

        $response = $this->captureJson(
            fn() => $this->controller->index(100)
        );

        $this->assertEquals(200, http_response_code());
        $this->assertTrue($response['success']);
    }
}