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
    public function it_can_index()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtenerFavoritos')
            ->with(1)
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
    public function it_can_store()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['propiedad_id' => 2]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('agregarFavorito')
            ->with(1, 2)
            ->willReturn(true);

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
        $input = json_encode(['propiedad_id' => 2]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->store($request);
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
            ->method('agregarFavorito')
            ->with(1, 2)
            ->willReturn(false);

        ob_start();
        $this->controller->store($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('409', $output);
    }

    /** @test */
    public function it_can_delete()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminarFavorito')
            ->with(1, 2)
            ->willReturn(true);

        ob_start();
        $this->controller->delete($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_delete_with_invalid_property_id()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        ob_start();
        $this->controller->delete($request, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_delete_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->delete($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_returns_not_found_when_delete_favorito_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminarFavorito')
            ->with(1, 999)
            ->willThrowException(new \Exception("La propiedad no está en favoritos", 404));

        ob_start();
        $this->controller->delete($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_check_favorito()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('esFavorito')
            ->with(1, 2)
            ->willReturn(true);

        ob_start();
        $this->controller->check($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('"es_favorito":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_check_with_invalid_property_id()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        ob_start();
        $this->controller->check($request, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_check_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->check($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_can_index_by_usuario()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtenerFavoritos')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->indexByUsuario($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_index_by_usuario_with_invalid_id()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        ob_start();
        $this->controller->indexByUsuario($request, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_can_delete_by_propiedad()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminarFavorito')
            ->with(1, 2)
            ->willReturn(true);

        ob_start();
        $this->controller->deleteByPropiedad($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_delete_by_propiedad_with_invalid_property_id()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        ob_start();
        $this->controller->deleteByPropiedad($request, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_delete_by_propiedad_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->deleteByPropiedad($request, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }
}