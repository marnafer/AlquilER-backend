<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\ReservaController;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Models\Reserva;
use App\Services\ReservaService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

final class ReservaControllerTest extends TestCase
{
    /** @var ReservaService&MockObject */
    private ReservaService $service;

    private ReservaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        Response::setTesting(true);

        $this->service = $this->createMock(
            ReservaService::class
        );

        $this->controller = new ReservaController(
            $this->service
        );

        $this->actingAs(1, 1);
    }

    protected function tearDown(): void
    {
        Request::setTestBody(null);

        unset($_SERVER['HTTP_AUTHORIZATION']);

        unset(
            $_GET['estado'],
            $_GET['usuario_id'],
            $_GET['propiedad_id']
        );

        Response::setTesting(false);

        parent::tearDown();
    }

    public function test_el_controlador_puede_listar_reservas(): void
    {
        $reservas = [
            [
                'id' => 1,
                'propiedad_id' => 10,
                'usuario_id' => 1,
                'estado' => 'pendiente',
            ],
            [
                'id' => 2,
                'propiedad_id' => 20,
                'usuario_id' => 5,
                'estado' => 'confirmada',
            ],
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(1, 1, [])
            ->willReturn($reservas);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);
        $this->assertSame($reservas, $response['data']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_listar_reservas_con_filtros(): void
    {
        $_GET['estado'] = 'pendiente';
        $_GET['usuario_id'] = '5';
        $_GET['propiedad_id'] = '10';

        $filtrosEsperados = [
            'estado' => 'pendiente',
            'usuario_id' => 5,
            'propiedad_id' => 10,
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(1, 1, $filtrosEsperados)
            ->willReturn([]);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_una_reserva(): void
    {
        $reserva = $this->createMock(Reserva::class);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1, 1, 1)
            ->willReturn($reserva);

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_crear_una_reserva(): void
    {
        $datos = [
            'propiedad_id' => 10,
        ];

        Request::setTestBody(
            json_encode($datos, JSON_THROW_ON_ERROR)
        );

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with($datos, 1)
            ->willReturn(15);

        $response = $this->captureJson(
            fn() => $this->controller->store()
        );

        $this->assertTrue(
            $response['success'] ?? false,
            json_encode(
                $response,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );

        $this->assertSame(
            ['id' => 15],
            $response['data']
        );

        $this->assertSame(
            'Reserva creada correctamente',
            $response['message']
        );

        $this->assertSame(201, http_response_code());
    }

    public function test_el_controlador_puede_confirmar_una_reserva(): void
    {
        $this->service
            ->expects($this->once())
            ->method('confirmar')
            ->with(1, 1, 1)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->confirm(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Reserva confirmada correctamente',
            $response['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_rechazar_una_reserva(): void
    {
        $this->service
            ->expects($this->once())
            ->method('rechazar')
            ->with(1, 1, 1)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->reject(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Reserva rechazada correctamente',
            $response['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_actualizar_una_reserva(): void
    {
        $datos = [
            'estado' => 'confirmada',
        ];

        Request::setTestBody(
            json_encode($datos, JSON_THROW_ON_ERROR)
        );

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, $datos, 1, 1)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->update(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Reserva actualizada correctamente',
            $response['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_eliminar_una_reserva(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 1, 1)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Reserva eliminada correctamente',
            $response['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_restaurar_una_reserva(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1, 1, 1)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->restore(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Reserva restaurada correctamente',
            $response['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_verifica_el_token_al_crear_una_reserva(): void
    {
        $datos = [
            'propiedad_id' => 10,
        ];

        $tokenProvider = $this->createMock(
            \App\Helpers\TokenProviderInterface::class
        );

        $tokenProvider
            ->expects($this->once())
            ->method('validate')
            ->with('token_usuario')
            ->willReturn((object) [
                'sub' => 1,
                'rol_id' => 1,
                'email' => 'test@test.com',
            ]);

        AutenticadorMiddleware::configure(
            $tokenProvider
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_usuario';

        Request::setTestBody(
            json_encode($datos, JSON_THROW_ON_ERROR)
        );

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with($datos, 1)
            ->willReturn(15);

        $response = $this->captureJson(
            fn() => $this->controller->store()
        );

        $this->assertTrue(
            $response['success'] ?? false,
            json_encode(
                $response,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }

    public function test_el_controlador_pasa_el_rol_al_confirmar(): void
    {
        $tokenProvider = $this->createMock(
            \App\Helpers\TokenProviderInterface::class
        );

        $tokenProvider
            ->expects($this->once())
            ->method('validate')
            ->with('token_admin')
            ->willReturn((object) [
                'sub' => 99,
                'rol_id' => 2,
                'email' => 'admin@test.com',
            ]);

        AutenticadorMiddleware::configure(
            $tokenProvider
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_admin';

        $this->service
            ->expects($this->once())
            ->method('confirmar')
            ->with(10, 99, 2)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->confirm(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Reserva confirmada correctamente',
            $response['message']
        );
    }

    public function test_el_controlador_pasa_el_rol_al_rechazar(): void
    {
        $tokenProvider = $this->createMock(
            \App\Helpers\TokenProviderInterface::class
        );

        $tokenProvider
            ->expects($this->once())
            ->method('validate')
            ->with('token_admin')
            ->willReturn((object) [
                'sub' => 99,
                'rol_id' => 2,
                'email' => 'admin@test.com',
            ]);

        AutenticadorMiddleware::configure(
            $tokenProvider
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_admin';

        $this->service
            ->expects($this->once())
            ->method('rechazar')
            ->with(10, 99, 2)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->reject(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Reserva rechazada correctamente',
            $response['message']
        );
    }

    public function test_el_controlador_pasa_el_rol_al_actualizar(): void
    {
        $datos = [
            'estado' => 'confirmada',
        ];

        Request::setTestBody(
            json_encode($datos, JSON_THROW_ON_ERROR)
        );

        $tokenProvider = $this->createMock(
            \App\Helpers\TokenProviderInterface::class
        );

        $tokenProvider
            ->expects($this->once())
            ->method('validate')
            ->with('token_admin')
            ->willReturn((object) [
                'sub' => 99,
                'rol_id' => 2,
                'email' => 'admin@test.com',
            ]);

        AutenticadorMiddleware::configure(
            $tokenProvider
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_admin';

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(10, $datos, 99, 2)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->update(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Reserva actualizada correctamente',
            $response['message']
        );
    }

    public function test_el_controlador_pasa_el_rol_al_eliminar(): void
    {
        $tokenProvider = $this->createMock(
            \App\Helpers\TokenProviderInterface::class
        );

        $tokenProvider
            ->expects($this->once())
            ->method('validate')
            ->with('token_admin')
            ->willReturn((object) [
                'sub' => 99,
                'rol_id' => 2,
                'email' => 'admin@test.com',
            ]);

        AutenticadorMiddleware::configure(
            $tokenProvider
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_admin';

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(10, 99, 2)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->delete(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Reserva eliminada correctamente',
            $response['message']
        );
    }

    public function test_el_controlador_pasa_el_rol_al_restaurar(): void
    {
        $tokenProvider = $this->createMock(
            \App\Helpers\TokenProviderInterface::class
        );

        $tokenProvider
            ->expects($this->once())
            ->method('validate')
            ->with('token_admin')
            ->willReturn((object) [
                'sub' => 99,
                'rol_id' => 2,
                'email' => 'admin@test.com',
            ]);

        AutenticadorMiddleware::configure(
            $tokenProvider
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_admin';

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(10, 99, 2)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->restore(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Reserva restaurada correctamente',
            $response['message']
        );
    }
}