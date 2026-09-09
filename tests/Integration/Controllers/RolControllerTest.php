<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\RolController;
use App\Services\RolService;

class RolControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(RolService::class);
        $this->controller = new RolController($this->service);
    }

    /** @test */
    public function it_can_index()
    {
        $this->service
            ->expects($this->once())
            ->method('listarRoles')
            ->willReturn([]);

        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_store()
    {
        $input = json_encode(['nombre' => 'Nuevo Rol']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crearRol')
            ->willReturn(1);

        ob_start();
        $this->controller->store();
        $output = ob_get_clean();

        $this->assertStringContainsString('201', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_store_missing_name()
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
            ->method('obtenerRol')
            ->with(1)
            ->willReturn((object) ['id' => 1]);

        ob_start();
        $this->controller->show(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_rol_not_exists()
    {
        $this->service
            ->expects($this->once())
            ->method('obtenerRol')
            ->with(999)
            ->willThrowException(new \Exception("Rol no encontrado", 404));

        ob_start();
        $this->controller->show(999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_update()
    {
        $input = json_encode(['nombre' => 'Rol Actualizado']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizarRol')
            ->with(1, ['nombre' => 'Rol Actualizado'])
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
            ->method('eliminarRol')
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
            ->method('restaurarRol')
            ->with(1)
            ->willReturn(true);

        ob_start();
        $this->controller->restore(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }
}