<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\PropiedadImagenController;
use App\Services\PropiedadImagenService;

class PropiedadImagenControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(PropiedadImagenService::class);
        $this->controller = new PropiedadImagenController($this->service);
    }

    /** @test */
    public function it_can_listar()
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn([]);

        ob_start();
        $this->controller->listar();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_crear()
    {
        // Simular upload de archivo
        $_FILES['imagen'] = [
            'name' => 'test.jpg',
            'tmp_name' => '/tmp/test.jpg',
            'error' => 0,
            'size' => 1024
        ];

        $input = json_encode(['propiedad_id' => 1]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->willReturn(1);

        ob_start();
        $this->controller->crear();
        $output = ob_get_clean();

        $this->assertStringContainsString('201', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_crear_missing_propiedad_id()
    {
        $_FILES['imagen'] = [
            'name' => 'test.jpg',
            'tmp_name' => '/tmp/test.jpg',
            'error' => 0,
            'size' => 1024
        ];

        $input = json_encode([]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crear();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_crear_missing_file()
    {
        // No enviar archivo
        $input = json_encode(['propiedad_id' => 1]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crear();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_can_obtener()
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn((object) ['id' => 1]);

        ob_start();
        $this->controller->obtener(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_imagen_not_exists()
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(new \Exception("Imagen no encontrada", 404));

        ob_start();
        $this->controller->obtener(999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_set_principal()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('setPrincipal')
            ->with(1)
            ->willReturn(true);

        ob_start();
        $this->controller->setPrincipal($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_set_principal_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->setPrincipal($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_can_eliminar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1)
            ->willReturn(true);

        ob_start();
        $this->controller->eliminar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_eliminar_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->eliminar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }
}