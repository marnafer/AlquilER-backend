<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\ResenaController;
use App\Services\ResenaService;

class ResenaControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(ResenaService::class);
        $this->controller = new ResenaController($this->service);
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
            ->willReturn((object) ['id' => 1, 'calificador_id' => 1, 'calificado_id' => 2]);

        ob_start();
        $this->controller->obtener($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_resena_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(new \Exception("Reseña no encontrada", 404));

        ob_start();
        $this->controller->obtener($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_returns_forbidden_when_user_not_authorized_to_view_resena()
    {
        $request = $this->createRequest(['usuario_id' => 3]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willThrowException(new \Exception("No autorizado", 403));

        ob_start();
        $this->controller->obtener($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('403', $output);
    }

    /** @test */
    public function it_can_crear_resena_propiedad()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([
            'reserva_id' => 5,
            'tipo' => 'propiedad',
            'calificacion' => 5,
            'comentario' => 'Excelente propiedad'
        ]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->willReturn(1);

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('201', $output);
    }

    /** @test */
    public function it_can_crear_resena_inquilino()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([
            'reserva_id' => 5,
            'tipo' => 'inquilino',
            'calificacion' => 4,
            'comentario' => 'Buen inquilino'
        ]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->willReturn(1);

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('201', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_crear_missing_fields()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_crear_missing_tipo()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([
            'reserva_id' => 5,
            'calificacion' => 5
        ]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_crear_without_user()
    {
        $request = $this->createRequest([]);
        $input = json_encode([
            'reserva_id' => 5,
            'tipo' => 'propiedad',
            'calificacion' => 5
        ]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_returns_conflict_when_resena_already_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([
            'reserva_id' => 5,
            'tipo' => 'propiedad',
            'calificacion' => 5
        ]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->willThrowException(new \Exception("Esta reserva ya tiene una reseña de tipo 'propiedad'", 409));

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('409', $output);
    }

    /** @test */
    public function it_can_actualizar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['calificacion' => 4]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, ['calificacion' => 4], 1)
            ->willReturn(true);

        ob_start();
        $this->controller->actualizar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_actualizar_with_empty_data()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->actualizar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_forbidden_when_actualizar_without_permission()
    {
        $request = $this->createRequest(['usuario_id' => 2]);
        $input = json_encode(['calificacion' => 4]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, ['calificacion' => 4], 2)
            ->willThrowException(new \Exception("No autorizado", 403));

        ob_start();
        $this->controller->actualizar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('403', $output);
    }

    /** @test */
    public function it_can_eliminar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 1)
            ->willReturn(true);

        ob_start();
        $this->controller->eliminar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_forbidden_when_eliminar_without_permission()
    {
        $request = $this->createRequest(['usuario_id' => 2]);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 2)
            ->willThrowException(new \Exception("No autorizado", 403));

        ob_start();
        $this->controller->eliminar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('403', $output);
    }

    /** @test */
    public function it_can_listar_por_reserva()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPorReserva')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->listarPorReserva($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_reserva_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPorReserva')
            ->with(999)
            ->willThrowException(new \Exception("La reserva no existe", 404));

        ob_start();
        $this->controller->listarPorReserva($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_listar_por_propiedad()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPorPropiedad')
            ->with(1)
            ->willReturn([]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPromedioPropiedad')
            ->with(1)
            ->willReturn(4.5);

        ob_start();
        $this->controller->listarPorPropiedad($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('"promedio":4.5', $output);
    }

    /** @test */
    public function it_returns_not_found_when_property_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPorPropiedad')
            ->with(999)
            ->willThrowException(new \Exception("La propiedad no existe", 404));

        ob_start();
        $this->controller->listarPorPropiedad($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_listar_por_usuario()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPorUsuario')
            ->with(1)
            ->willReturn([]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPromedioUsuario')
            ->with(1)
            ->willReturn(4.2);

        ob_start();
        $this->controller->listarPorUsuario($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('"promedio":4.2', $output);
    }

    /** @test */
    public function it_returns_not_found_when_usuario_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPorUsuario')
            ->with(999)
            ->willThrowException(new \Exception("El usuario no existe", 404));

        ob_start();
        $this->controller->listarPorUsuario($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_listar_por_calificador()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPorCalificador')
            ->with(1)
            ->willReturn([]);

        ob_start();
        $this->controller->listarPorCalificador($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_not_found_when_calificador_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('listarPorCalificador')
            ->with(999)
            ->willThrowException(new \Exception("El usuario no existe", 404));

        ob_start();
        $this->controller->listarPorCalificador($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }
}