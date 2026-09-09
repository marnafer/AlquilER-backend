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
    public function it_can_index()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarConsultas')
            ->willReturn([]);

        ob_start();
        $this->controller->index($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_index_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->index($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_can_show()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtenerConsulta')
            ->with(1)
            ->willReturn((object) ['id' => 1]);

        ob_start();
        $this->controller->show($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_consulta_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtenerConsulta')
            ->with(999)
            ->willThrowException(new \Exception("Consulta no encontrada", 404));

        ob_start();
        $this->controller->show($request, 999);
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
            ->method('obtenerConsulta')
            ->with(1)
            ->willThrowException(new \Exception("No autorizado", 403));

        ob_start();
        $this->controller->show($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('403', $output);
    }

    /** @test */
    public function it_can_store()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['propiedad_id' => 1]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crearConsulta')
            ->willReturn(1);

        ob_start();
        $this->controller->store($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('201', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_store_missing_propiedad_id()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->store($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_store_without_user()
    {
        $request = $this->createRequest([]);
        $input = json_encode(['propiedad_id' => 1]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->store($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_can_update()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['mensaje' => 'Mensaje actualizado']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizarConsulta')
            ->with(1, ['mensaje' => 'Mensaje actualizado'], 1)
            ->willReturn(true);

        ob_start();
        $this->controller->update($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_forbidden_when_update_without_permission()
    {
        $request = $this->createRequest(['usuario_id' => 2]);
        $input = json_encode(['mensaje' => 'Mensaje actualizado']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizarConsulta')
            ->with(1, ['mensaje' => 'Mensaje actualizado'], 2)
            ->willThrowException(new \Exception("No autorizado", 403));

        ob_start();
        $this->controller->update($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('403', $output);
    }

    /** @test */
    public function it_can_delete()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminarConsulta')
            ->with(1, 1)
            ->willReturn(true);

        ob_start();
        $this->controller->delete($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_forbidden_when_delete_without_permission()
    {
        $request = $this->createRequest(['usuario_id' => 2]);

        $this->service
            ->expects($this->once())
            ->method('eliminarConsulta')
            ->with(1, 2)
            ->willThrowException(new \Exception("No autorizado", 403));

        ob_start();
        $this->controller->delete($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('403', $output);
    }

    /** @test */
    public function it_can_restore()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('restaurarConsulta')
            ->with(1, 1)
            ->willReturn(true);

        ob_start();
        $this->controller->restore($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_get_by_usuario()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtenerConsultasPorUsuario')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->getByUsuario($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_get_by_propiedad()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtenerConsultasPorPropiedad')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->getByPropiedad($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_get_by_propiedad_and_property_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtenerConsultasPorPropiedad')
            ->with(999)
            ->willThrowException(new \Exception("Propiedad no encontrada", 404));

        ob_start();
        $this->controller->getByPropiedad($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }
}