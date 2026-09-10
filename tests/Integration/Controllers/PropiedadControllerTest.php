<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\PropiedadController;
use App\Services\PropiedadService;

class PropiedadControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(PropiedadService::class);
        $this->controller = new PropiedadController($this->service);
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
            'titulo' => 'Casa Test',
            'descripcion' => 'Descripción de la propiedad',
            'precio' => 150000.00,
            'expensas' => 5000.00,
            'direccion' => 'Calle 123',
            'cantidad_ambientes' => 3,
            'cantidad_dormitorios' => 2,
            'cantidad_banos' => 1,
            'capacidad' => 4,
            'disponible' => true,
            'categoria_id' => 1,
            'usuario_id' => 1,
            'localidad_id' => 1
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
    public function it_returns_not_found_when_propiedad_not_exists()
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(new \Exception("Propiedad no encontrada", 404));

        ob_start();
        $this->controller->obtener(999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_actualizar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['titulo' => 'Propiedad Actualizada']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, ['titulo' => 'Propiedad Actualizada'])
            ->willReturn(true);

        ob_start();
        $this->controller->actualizar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_actualizar_without_user()
    {
        $request = $this->createRequest([]);
        $input = json_encode(['titulo' => 'Propiedad Actualizada']);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->actualizar($request, 1);
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
}