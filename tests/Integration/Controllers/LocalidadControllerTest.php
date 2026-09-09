<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\LocalidadController;
use App\Services\LocalidadService;

class LocalidadControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(LocalidadService::class);
        $this->controller = new LocalidadController($this->service);
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
        $input = json_encode([
            'nombre' => 'Nueva Localidad',
            'codigo_postal' => '1234',
            'provincia_id' => 1
        ]);
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
    public function it_returns_bad_request_when_crear_missing_fields()
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
    public function it_returns_not_found_when_localidad_not_exists()
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(new \Exception("Localidad no encontrada", 404));

        ob_start();
        $this->controller->obtener(999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_actualizar()
    {
        $input = json_encode(['nombre' => 'Localidad Actualizada']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, ['nombre' => 'Localidad Actualizada'])
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