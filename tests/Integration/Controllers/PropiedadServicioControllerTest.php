<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\PropiedadServicioController;
use App\Services\PropiedadServicioService;

class PropiedadServicioControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(PropiedadServicioService::class);
        $this->controller = new PropiedadServicioController($this->service);
    }

    /** @test */
    public function it_can_listar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->listar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_listar_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->listar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_returns_not_found_when_property_not_exists_in_listar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(999)
            ->willThrowException(new \Exception("La propiedad no existe", 404));

        ob_start();
        $this->controller->listar($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_listar_propiedades_por_servicio()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPropiedadesPorServicio')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->listarPropiedadesPorServicio($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_servicio_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPropiedadesPorServicio')
            ->with(999)
            ->willThrowException(new \Exception("El servicio no existe", 404));

        ob_start();
        $this->controller->listarPropiedadesPorServicio($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_crear()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['servicio_id' => 2]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(1, 2, 1)
            ->willReturn(true);

        ob_start();
        $this->controller->crear($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('201', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_crear_missing_servicio_id()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crear($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_crear_without_user()
    {
        $request = $this->createRequest([]);
        $input = json_encode(['servicio_id' => 2]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crear($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_returns_conflict_when_servicio_already_assigned()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['servicio_id' => 2]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(1, 2, 1)
            ->willReturn(false);

        ob_start();
        $this->controller->crear($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('409', $output);
    }

    /** @test */
    public function it_can_crear_multiples()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['servicio_ids' => [1, 2, 3]]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crearMultiples')
            ->with(1, [1, 2, 3], 1)
            ->willReturn(['asignados' => [1, 2], 'duplicados' => [3], 'errores' => []]);

        ob_start();
        $this->controller->crearMultiples($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_crear_multiples_missing_servicio_ids()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crearMultiples($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_crear_multiples_servicio_ids_not_array()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['servicio_ids' => 'not_an_array']);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crearMultiples($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_can_sincronizar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['servicio_ids' => [1, 3]]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('sincronizar')
            ->with(1, [1, 3], 1)
            ->willReturn([
                'agregados' => [3],
                'eliminados' => [2],
                'mantenidos' => [1]
            ]);

        ob_start();
        $this->controller->sincronizar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_sincronizar_missing_servicio_ids()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->sincronizar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_sincronizar_without_user()
    {
        $request = $this->createRequest([]);
        $input = json_encode(['servicio_ids' => [1, 3]]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->sincronizar($request, 1);
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
            ->with(1, 2, 1)
            ->willReturn(true);

        ob_start();
        $this->controller->eliminar($request, 1, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_eliminar_servicio_not_assigned()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 999, 1)
            ->willThrowException(new \Exception("La propiedad no tiene este servicio asignado", 404));

        ob_start();
        $this->controller->eliminar($request, 1, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_eliminar_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->eliminar($request, 1, 2);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }
}