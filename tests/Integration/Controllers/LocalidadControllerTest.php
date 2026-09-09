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
    public function it_can_index()
    {
        $this->service
            ->expects($this->once())
            ->method('listarLocalidades')
            ->willReturn([]);

        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_store()
    {
        $input = json_encode([
            'nombre' => 'Nueva Localidad',
            'codigo_postal' => '1234',
            'provincia_id' => 1
        ]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crearLocalidad')
            ->willReturn(1);

        ob_start();
        $this->controller->store();
        $output = ob_get_clean();

        $this->assertStringContainsString('201', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_store_missing_fields()
    {
        $input = json_encode([]);
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
            ->method('obtenerLocalidad')
            ->with(1)
            ->willReturn((object) ['id' => 1]);

        ob_start();
        $this->controller->show(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_localidad_not_exists()
    {
        $this->service
            ->expects($this->once())
            ->method('obtenerLocalidad')
            ->with(999)
            ->willThrowException(new \Exception("Localidad no encontrada", 404));

        ob_start();
        $this->controller->show(999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_update()
    {
        $input = json_encode(['nombre' => 'Localidad Actualizada']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizarLocalidad')
            ->with(1, ['nombre' => 'Localidad Actualizada'])
            ->willReturn(true);

        ob_start();
        $this->controller->update(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_delete()
    {
        $this->service
            ->expects($this->once())
            ->method('eliminarLocalidad')
            ->with(1)
            ->willReturn(true);

        ob_start();
        $this->controller->delete(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_restore()
    {
        $this->service
            ->expects($this->once())
            ->method('restaurarLocalidad')
            ->with(1)
            ->willReturn(true);

        ob_start();
        $this->controller->restore(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }
}