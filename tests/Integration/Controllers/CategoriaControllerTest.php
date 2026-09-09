<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\CategoriaController;
use App\Services\CategoriaService;

class CategoriaControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(CategoriaService::class);
        $this->controller = new CategoriaController($this->service);
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
        $input = json_encode(['nombre' => 'Nueva Categoria']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->willReturn(1);

        ob_start();
        $this->controller->crear();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('201', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_crear_missing_name()
    {
        $input = json_encode([]);
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
    public function it_returns_not_found_when_categoria_not_exists()
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(new \Exception("Categoría no encontrada", 404));

        ob_start();
        $this->controller->obtener(999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_actualizar()
    {
        $input = json_encode(['nombre' => 'Categoria Actualizada']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, ['nombre' => 'Categoria Actualizada'])
            ->willReturn(true);

        ob_start();
        $this->controller->actualizar(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_eliminar()
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1)
            ->willReturn(true);

        ob_start();
        $this->controller->eliminar(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_restaurar()
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1)
            ->willReturn(true);

        ob_start();
        $this->controller->restaurar(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }
}