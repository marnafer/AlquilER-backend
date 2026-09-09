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
    public function it_can_index()
    {
        $this->service
            ->expects($this->once())
            ->method('listarImagenes')
            ->willReturn([]);

        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_store()
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
            ->method('crearImagen')
            ->willReturn(1);

        ob_start();
        $this->controller->store();
        $output = ob_get_clean();

        $this->assertStringContainsString('201', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_store_missing_propiedad_id()
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
        $this->controller->store();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_store_missing_file()
    {
        // No enviar archivo
        $input = json_encode(['propiedad_id' => 1]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->store();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_can_show()
    {
        $this->service
            ->expects($this->once())
            ->method('obtenerImagen')
            ->with(1)
            ->willReturn((object) ['id' => 1]);

        ob_start();
        $this->controller->show(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_imagen_not_exists()
    {
        $this->service
            ->expects($this->once())
            ->method('obtenerImagen')
            ->with(999)
            ->willThrowException(new \Exception("Imagen no encontrada", 404));

        ob_start();
        $this->controller->show(999);
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
    public function it_can_delete()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminarImagen')
            ->with(1)
            ->willReturn(true);

        ob_start();
        $this->controller->delete($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_delete_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->delete($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }
}