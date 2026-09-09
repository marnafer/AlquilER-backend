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
    public function it_can_index()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarUsuarios')
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
            ->method('obtenerUsuario')
            ->with(1)
            ->willReturn((object) ['id' => 1]);

        ob_start();
        $this->controller->show($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_usuario_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtenerUsuario')
            ->with(999)
            ->willThrowException(new \Exception("Usuario no encontrado", 404));

        ob_start();
        $this->controller->show($request, 999);
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
            ->method('obtenerUsuario')
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
    public function it_can_update()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['nombre' => 'Nombre Actualizado']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizarUsuario')
            ->with(1, ['nombre' => 'Nombre Actualizado'])
            ->willReturn(true);

        ob_start();
        $this->controller->update($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_can_delete()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminarUsuario')
            ->with(1)
            ->willReturn(true);

        ob_start();
        $this->controller->delete($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }
}