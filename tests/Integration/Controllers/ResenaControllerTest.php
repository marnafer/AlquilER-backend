<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\ResenaController;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Models\Resena;
use App\Services\ResenaService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ResenaControllerTest extends TestCase
{
    /** @var ResenaService&MockObject */
    private ResenaService $service;
    private ResenaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        Response::setTesting(true);

        $this->service = $this->createMock(ResenaService::class);
        $this->controller = new ResenaController($this->service);

        $this->actingAs(1, 1);
    }

    protected function tearDown(): void
    {
        Request::setTestBody(null);

        unset($_SERVER['HTTP_AUTHORIZATION']);

        unset(
            $_GET['tipo'],
            $_GET['calificacion'],
            $_GET['calificacion_min'],
            $_GET['calificacion_max'],
            $_GET['calificador_id'],
            $_GET['reserva_id'],
            $_GET['propiedad_id'],
            $_GET['usuario_id'],
            $_GET['fecha_desde'],
            $_GET['fecha_hasta']
        );

        Response::setTesting(false);

        parent::tearDown();
    }

    public function test_el_controlador_puede_listar_resenas(): void
    {
        $resenas = [
            [
                'id' => 1,
                'reserva_id' => 10,
                'calificacion' => 5,
                'comentario' => 'Excelente'
            ],
            [
                'id' => 2,
                'reserva_id' => 11,
                'calificacion' => 4,
                'comentario' => 'Muy buena'
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn([
                'items' => $resenas,
                'total' => 2
            ]);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);
        $this->assertSame($resenas, $response['data']['items']);
        $this->assertSame(2, $response['data']['total']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_listar_resenas_con_filtros(): void
    {
        $_GET['tipo'] = 'propietario';
        $_GET['calificacion'] = '5';
        $_GET['calificacion_min'] = '3';
        $_GET['calificacion_max'] = '5';
        $_GET['calificador_id'] = '10';
        $_GET['reserva_id'] = '20';
        $_GET['propiedad_id'] = '30';
        $_GET['usuario_id'] = '40';
        $_GET['fecha_desde'] = '2026-01-01';
        $_GET['fecha_hasta'] = '2026-12-31';

        $filtrosEsperados = [
            'tipo' => 'propietario',
            'calificacion' => 5,
            'calificacion_min' => 3,
            'calificacion_max' => 5,
            'calificador_id' => 10,
            'reserva_id' => 20,
            'propiedad_id' => 30,
            'usuario_id' => 40,
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => '2026-12-31'
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with($filtrosEsperados)
            ->willReturn([
                'items' => [],
                'total' => 0
            ]);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']['items']);
        $this->assertSame(0, $response['data']['total']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_una_resena(): void
    {
        $resena = $this->createMock(Resena::class);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($resena);

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_resenas_por_reserva(): void
    {
        $resenas = new Collection([
            ['id' => 1, 'reserva_id' => 10],
            ['id' => 2, 'reserva_id' => 10]
        ]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPorReserva')
            ->with(10)
            ->willReturn($resenas);

        $response = $this->captureJson(
            fn() => $this->controller->getByReserva(10)
        );

        $this->assertTrue($response['success']);
        $this->assertCount(2, $response['data']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_resenas_por_propiedad(): void
    {
        $resenas = new Collection([
            ['id' => 1, 'calificacion' => 5],
            ['id' => 2, 'calificacion' => 4]
        ]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPorPropiedad')
            ->with(30)
            ->willReturn($resenas);

        $this->service
            ->expects($this->once())
            ->method('promedioPropiedad')
            ->with(30)
            ->willReturn(4.5);

        $response = $this->captureJson(
            fn() => $this->controller->getByPropiedad(30)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(2, $response['data']['total']);
        $this->assertSame(4.5, $response['data']['promedio']);
        $this->assertCount(2, $response['data']['items']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_resenas_por_usuario(): void
    {
        $resenas = new Collection([
            ['id' => 1, 'calificacion' => 5],
            ['id' => 2, 'calificacion' => 3]
        ]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPorUsuario')
            ->with(40)
            ->willReturn($resenas);

        $this->service
            ->expects($this->once())
            ->method('promedioUsuario')
            ->with(40)
            ->willReturn(4.0);

        $response = $this->captureJson(
            fn() => $this->controller->getByUsuario(40)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(2, $response['data']['total']);
        $this->assertEquals(4.0, $response['data']['promedio']);
        $this->assertCount(2, $response['data']['items']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_resenas_por_calificador(): void
    {
        $resenas = new Collection([
            ['id' => 1, 'calificador_id' => 50],
            ['id' => 2, 'calificador_id' => 50]
        ]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPorCalificador')
            ->with(50)
            ->willReturn($resenas);

        $response = $this->captureJson(
            fn() => $this->controller->getByCalificador(50)
        );

        $this->assertTrue($response['success']);
        $this->assertCount(2, $response['data']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_crear_una_resena(): void
    {
        $datos = [
            'reserva_id' => 10,
            'tipo' => 'propietario',
            'calificacion' => 5,
            'comentario' => 'Excelente propiedad'
        ];

        $resena = $this->createMock(Resena::class);

        Request::setTestBody(
            json_encode($datos, JSON_THROW_ON_ERROR)
        );

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with($datos, 1)
            ->willReturn($resena);

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

        $this->assertArrayHasKey('data', $response);
        $this->assertSame(
            'Reseña creada exitosamente',
            $response['message']
        );
    }

    public function test_el_controlador_verifica_el_token_al_crear_una_resena(): void
    {
        $datos = [
            'reserva_id' => 10,
            'tipo' => 'propietario',
            'calificacion' => 5,
            'comentario' => 'Excelente propiedad'
        ];

        $resena = $this->createMock(Resena::class);

        $tokenProvider = $this->createMock(\App\Helpers\TokenProviderInterface::class);

        $tokenProvider
            ->expects($this->once())
            ->method('validate')
            ->with('token_usuario')
            ->willReturn((object) [
                'sub' => 1,
                'rol_id' => 1,
                'email' => 'test@test.com'
            ]);

        AutenticadorMiddleware::configure($tokenProvider);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token_usuario';

        Request::setTestBody(
            json_encode($datos, JSON_THROW_ON_ERROR)
        );

        $this->service
            ->method('crear')
            ->willReturn($resena);

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

    public function test_el_controlador_puede_eliminar_una_resena(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 1, 1);

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertTrue(
            $response['success'] ?? false,
            json_encode(
                $response,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );

        $this->assertSame(
            'Reseña eliminada exitosamente',
            $response['message']
        );
    }

    public function test_el_controlador_verifica_el_token_al_eliminar_una_resena(): void
    {
        $tokenProvider = $this->createMock(\App\Helpers\TokenProviderInterface::class);

        $tokenProvider
            ->expects($this->once())
            ->method('validate')
            ->with('token_usuario')
            ->willReturn((object) [
                'sub' => 1,
                'rol_id' => 1,
                'email' => 'test@test.com'
            ]);

        AutenticadorMiddleware::configure($tokenProvider);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token_usuario';

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 1, 1);

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertTrue(
            $response['success'] ?? false,
            json_encode(
                $response,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }
}