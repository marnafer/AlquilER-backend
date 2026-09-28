<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Models\PropiedadImagen;
use App\Controllers\Api\PropiedadImagenController;
use App\Services\PropiedadImagenService;
use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;

class PropiedadImagenControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(PropiedadImagenService::class);
        $this->controller = new PropiedadImagenController($this->service);

        // El controller lee el upload de $_POST / $_FILES directamente.
        $_POST = [];
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_FILES = [];
        unset($_SERVER['HTTP_AUTHORIZATION']);

        parent::tearDown();
    }

    private function imagen(int $id = 1): PropiedadImagen
    {
        $imagen = new PropiedadImagen();
        $imagen->id = $id;

        return $imagen;
    }

    private function conUpload(): void
    {
        $_POST = ['propiedad_id' => '1'];
        $_FILES = [
            'imagen' => [
                'name' => 'test.jpg',
                'tmp_name' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.jpg',
                'error' => 0,
                'size' => 1024
            ]
        ];
    }

    public function test_can_listar(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn(['items' => [], 'total' => 0]);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(['items' => [], 'total' => 0], $response['data']);
        $this->assertSame(200, http_response_code());
    }

    public function test_can_obtener(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($this->imagen(1));

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(200, http_response_code());
    }

    public function test_returns_not_found_when_imagen_not_exists(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(
                new NotFoundException('Imagen no encontrada')
            );

        $response = $this->captureJson(
            fn() => $this->controller->show(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame('Imagen no encontrada', $response['error']);
        $this->assertSame(404, http_response_code());
    }

    public function test_can_crear(): void
    {
        $this->actingAs(1, 1);
        $this->conUpload();

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->willReturn($this->imagen());

        $response = $this->captureJson(
            fn() => $this->controller->store()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(201, http_response_code());
    }

    public function test_returns_unauthorized_when_crear_without_auth(): void
    {
        $this->conUpload();

        $this->service
            ->expects($this->never())
            ->method('crear');

        $response = $this->captureJson(
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(401, http_response_code());
    }

    public function test_returns_bad_request_when_crear_missing_file(): void
    {
        $this->actingAs(1, 1);
        // $_POST con propiedad_id pero sin archivo
        $_POST = ['propiedad_id' => '1'];

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->willThrowException(
                new BadRequestException('La imagen es obligatoria')
            );

        $response = $this->captureJson(
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame('La imagen es obligatoria', $response['error']);
        $this->assertSame(400, http_response_code());
    }

    public function test_returns_bad_request_when_crear_missing_propiedad_id(): void
    {
        $this->actingAs(1, 1);
        $this->conUpload();
        $_POST = [];

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->willThrowException(
                new BadRequestException('La propiedad es obligatoria')
            );

        $response = $this->captureJson(
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame('La propiedad es obligatoria', $response['error']);
        $this->assertSame(400, http_response_code());
    }

    public function test_can_set_principal(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->once())
            ->method('establecerPrincipal')
            ->with(1, 1, 1)
            ->willReturn($this->imagen(1));

        $response = $this->captureJson(
            fn() => $this->controller->setPrincipal(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(200, http_response_code());
    }

    public function test_returns_unauthorized_when_set_principal_without_auth(): void
    {
        $this->service
            ->expects($this->never())
            ->method('establecerPrincipal');

        $response = $this->captureJson(
            fn() => $this->controller->setPrincipal(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(401, http_response_code());
    }

    public function test_can_eliminar(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 1, 1);

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(200, http_response_code());
    }

    public function test_returns_unauthorized_when_eliminar_without_auth(): void
    {
        $this->service
            ->expects($this->never())
            ->method('eliminar');

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(401, http_response_code());
    }
}
