<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\UsuarioController;
use App\Services\UsuarioService;

class UsuarioControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(UsuarioService::class);
        $this->controller = new UsuarioController($this->service);
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
    public function it_returns_not_found_when_usuario_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(new \Exception("Usuario no encontrado", 404));

        ob_start();
        $this->controller->obtener($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_profile()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn((object) ['id' => 1]);

        ob_start();
        $this->controller->profile($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_profile_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->profile($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_can_actualizar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['nombre' => 'Nombre Actualizado']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, ['nombre' => 'Nombre Actualizado'])
            ->willReturn(true);

        ob_start();
        $this->controller->actualizar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
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
    public function it_can_restaurar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1)
            ->willReturn(true);

        ob_start();
        $this->controller->restaurar($request, 1);
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