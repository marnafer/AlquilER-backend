<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\ConsultaController;
use App\Services\ConsultaService;

class ConsultaControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(ConsultaService::class);
        $this->controller = new ConsultaController($this->service);
    }

    /** @test */
    public function it_can_listar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn([]);

        ob_start();
        $this->controller->listar($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_listar_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->listar($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_can_obtener()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn((object) ['id' => 1]);

        ob_start();
        $this->controller->obtener($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_consulta_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(new \Exception("Consulta no encontrada", 404));

        ob_start();
        $this->controller->obtener($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_returns_forbidden_when_user_not_authorized_to_view_consulta()
    {
        $request = $this->createRequest(['usuario_id' => 2]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willThrowException(new \Exception("No autorizado", 403));

        ob_start();
        $this->controller->obtener($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('403', $output);
    }

    /** @test */
    public function it_can_crear()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['propiedad_id' => 1]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->willReturn(1);

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('201', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_crear_missing_propiedad_id()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_crear_without_user()
    {
        $request = $this->createRequest([]);
        $input = json_encode(['propiedad_id' => 1]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_can_actualizar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['mensaje' => 'Mensaje actualizado']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, ['mensaje' => 'Mensaje actualizado'], 1)
            ->willReturn(true);

        ob_start();
        $this->controller->actualizar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_forbidden_when_actualizar_without_permission()
    {
        $request = $this->createRequest(['usuario_id' => 2]);
        $input = json_encode(['mensaje' => 'Mensaje actualizado']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, ['mensaje' => 'Mensaje actualizado'], 2)
            ->willThrowException(new \Exception("No autorizado", 403));

        ob_start();
        $this->controller->actualizar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('403', $output);
    }

    /** @test */
    public function it_can_eliminar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 1)
            ->willReturn(true);

        ob_start();
        $this->controller->eliminar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_forbidden_when_eliminar_without_permission()
    {
        $request = $this->createRequest(['usuario_id' => 2]);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 2)
            ->willThrowException(new \Exception("No autorizado", 403));

        ob_start();
        $this->controller->eliminar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('403', $output);
    }

    /** @test */
    public function it_can_restaurar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1, 1)
            ->willReturn(true);

        ob_start();
        $this->controller->restaurar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_listar_por_usuario()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPorUsuario')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->listarPorUsuario($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_listar_por_propiedad()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPorPropiedad')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->listarPorPropiedad($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_listar_por_propiedad_and_property_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPorPropiedad')
            ->with(999)
            ->willThrowException(new \Exception("Propiedad no encontrada", 404));

        ob_start();
        $this->controller->listarPorPropiedad($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }
}