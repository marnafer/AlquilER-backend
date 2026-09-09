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
    public function it_can_index()
    {
        $this->service
            ->expects($this->once())
            ->method('listarPropiedades')
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
            ->method('crearPropiedad')
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
            ->method('obtenerPropiedad')
            ->with(1)
            ->willReturn((object) ['id' => 1]);

        ob_start();
        $this->controller->show(1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_propiedad_not_exists()
    {
        $this->service
            ->expects($this->once())
            ->method('obtenerPropiedad')
            ->with(999)
            ->willThrowException(new \Exception("Propiedad no encontrada", 404));

        ob_start();
        $this->controller->show(999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_update()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['titulo' => 'Propiedad Actualizada']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizarPropiedad')
            ->with(1, ['titulo' => 'Propiedad Actualizada'])
            ->willReturn(true);

        ob_start();
        $this->controller->update($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_update_without_user()
    {
        $request = $this->createRequest([]);
        $input = json_encode(['titulo' => 'Propiedad Actualizada']);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->update($request, 1);
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
            ->method('eliminarPropiedad')
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

    /** @test */
    public function it_can_restore()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('restaurarPropiedad')
            ->with(1)
            ->willReturn(true);

        ob_start();
        $this->controller->restore($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }
}