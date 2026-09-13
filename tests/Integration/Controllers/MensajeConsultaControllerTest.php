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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
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

        ob_start();
        $this->controller->index(100);
        $output = ob_get_clean();

        $this->assertEquals(200, http_response_code());
        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('"items":', $output);
        $this->assertStringContainsString('"total":2', $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_store_retorna_201_y_crea_el_mensaje_correctamente(): void
    {
        $input = json_encode(['mensaje' => 'Me interesa']);
        file_put_contents('php://input', $input);

        $mensajeCreado = new MensajeConsulta(['id' => 1, 'mensaje' => 'Me interesa']);

        $this->service->expects($this->once())
            ->method('crearMensaje')
            ->willReturn($mensajeCreado);

        ob_start();
        $this->controller->store(100);
        $output = ob_get_clean();

        $this->assertEquals(201, http_response_code());
        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('Me interesa', $output);
    }
}