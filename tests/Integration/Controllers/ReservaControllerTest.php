<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\ReservaController;
use App\Services\ReservaService;

class ReservaControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(ReservaService::class);
        $this->controller = new ReservaController($this->service);
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
    public function it_can_listar_with_filters()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $_GET['estado'] = 'pendiente';
        $_GET['usuario_id'] = '1';

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(['estado' => 'pendiente', 'usuario_id' => 1])
            ->willReturn([]);

        ob_start();
        $this->controller->listar($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
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
    public function it_returns_not_found_when_reserva_not_exists()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(new \Exception("Reserva no encontrada", 404));

        ob_start();
        $this->controller->obtener($request, 999);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }

    /** @test */
    public function it_can_crear()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([
            'propiedad_id' => 1,
            'usuario_id' => 1,
            'fecha_inicio_alquiler' => '2026-07-01',
            'fecha_fin_alquiler' => '2026-07-15'
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
    public function it_returns_bad_request_when_crear_missing_dates()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([
            'propiedad_id' => 1,
            'usuario_id' => 1
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
            'propiedad_id' => 1,
            'usuario_id' => 1,
            'fecha_inicio_alquiler' => '2026-07-01',
            'fecha_fin_alquiler' => '2026-07-15'
        ]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->crear($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_returns_conflict_when_property_not_available()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([
            'propiedad_id' => 1,
            'usuario_id' => 1,
            'fecha_inicio_alquiler' => '2026-07-01',
            'fecha_fin_alquiler' => '2026-07-15'
        ]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->willThrowException(new \Exception("La propiedad no está disponible en ese rango de fechas", 409));

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
        $input = json_encode(['fecha_fin_alquiler' => '2026-07-20']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, ['fecha_fin_alquiler' => '2026-07-20'])
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
    public function it_returns_unauthorized_when_actualizar_without_user()
    {
        $request = $this->createRequest([]);
        $input = json_encode(['fecha_fin_alquiler' => '2026-07-20']);
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
            ->with(1, 1)
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
    public function it_returns_forbidden_when_eliminar_confirmada_reserva()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 1)
            ->willThrowException(new \Exception("No se puede eliminar una reserva confirmada o finalizada", 400));

        ob_start();
        $this->controller->eliminar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_can_restaurar()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1, 1)
            ->willReturn(true);

        ob_start();
        $this->controller->restaurar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_restaurar_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->restaurar($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
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

        ob_start();
        $this->controller->listarPorUsuario($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_listar_por_usuario_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->listarPorUsuario($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
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

        ob_start();
        $this->controller->listarPorPropiedad($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_listar_por_propiedad_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->listarPorPropiedad($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_can_cambiar_estado()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['estado' => 'confirmada']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('cambiarEstado')
            ->with(1, 'confirmada', 1)
            ->willReturn(true);

        ob_start();
        $this->controller->cambiarEstado($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_cambiar_estado_missing_estado()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode([]);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->cambiarEstado($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_cambiar_estado_without_user()
    {
        $request = $this->createRequest([]);
        $input = json_encode(['estado' => 'confirmada']);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->cambiarEstado($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_cambiar_estado_invalid_transition()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $input = json_encode(['estado' => 'pendiente']);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('cambiarEstado')
            ->with(1, 'pendiente', 1)
            ->willThrowException(new \Exception("No se puede cambiar de 'finalizada' a 'pendiente'", 400));

        ob_start();
        $this->controller->cambiarEstado($request, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_can_verificar_disponibilidad()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $_GET['propiedad_id'] = '1';
        $_GET['fecha_inicio'] = '2026-07-01';
        $_GET['fecha_fin'] = '2026-07-15';

        $this->service
            ->expects($this->once())
            ->method('verificarDisponibilidad')
            ->with(1, '2026-07-01', '2026-07-15')
            ->willReturn(true);

        ob_start();
        $this->controller->verificarDisponibilidad($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('"disponible":true', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_verificar_disponibilidad_missing_params()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        ob_start();
        $this->controller->verificarDisponibilidad($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_returns_not_found_when_property_not_exists_for_availability()
    {
        $request = $this->createRequest(['usuario_id' => 1]);
        $_GET['propiedad_id'] = '999';
        $_GET['fecha_inicio'] = '2026-07-01';
        $_GET['fecha_fin'] = '2026-07-15';

        $this->service
            ->expects($this->once())
            ->method('verificarDisponibilidad')
            ->with(999, '2026-07-01', '2026-07-15')
            ->willThrowException(new \Exception("La propiedad no existe", 404));

        ob_start();
        $this->controller->verificarDisponibilidad($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
    }
}