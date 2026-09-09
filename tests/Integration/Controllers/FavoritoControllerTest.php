<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\FavoritoController;
use App\Services\FavoritoService;

class FavoritoControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(FavoritoService::class);
        $this->controller = new FavoritoController($this->service);
    }

    /** @test */
    public function it_can_listar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(1)
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
    public function it_can_crear()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['propiedad_id' => 2]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(1, 2)
            ->willReturn(true);

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
        $input = json_encode(['propiedad_id' => 2]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_returns_conflict_when_favorito_already_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['propiedad_id' => 2]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(1, 2)
            ->willReturn(false);

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('409', $output);
    }

    /** @test */
    public function it_can_eliminar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 2)
            ->willReturn(true);

        ob_start();
        $this->controller->eliminar($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_eliminar_with_invalid_property_id()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        ob_start();
        $this->controller->eliminar($request, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_eliminar_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->eliminar($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_returns_not_found_when_eliminar_favorito_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 999)
            ->willThrowException(new \Exception("La propiedad no está en favoritos", 404));

        ob_start();
        $this->controller->eliminar($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_verificar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('verificar')
            ->with(1, 2)
            ->willReturn(true);

        ob_start();
        $this->controller->verificar($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('"es_favorito":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_verificar_with_invalid_property_id()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        ob_start();
        $this->controller->verificar($request, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_verificar_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->verificar($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_can_listar_por_usuario()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->listarPorUsuario($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_listar_por_usuario_with_invalid_id()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        ob_start();
        $this->controller->listarPorUsuario($request, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_can_eliminar_por_propiedad()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 2)
            ->willReturn(true);

        ob_start();
        $this->controller->eliminarPorPropiedad($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_eliminar_por_propiedad_with_invalid_property_id()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        ob_start();
        $this->controller->eliminarPorPropiedad($request, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_eliminar_por_propiedad_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->eliminarPorPropiedad($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }
}