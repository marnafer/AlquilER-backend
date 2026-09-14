<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\LogActividadController;
use App\Services\LogActividadService;
use App\Helpers\Request;
use App\Helpers\TokenProviderInterface;
use App\Middlewares\AutenticadorMiddleware;
use App\Exceptions\NotFoundException;

class LogActividadControllerTest extends TestCase
{
    private $controller;
    private $service;
    private $tokenProviderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(
            LogActividadService::class
        );

        $this->controller = new LogActividadController(
            $this->service
        );

        $this->tokenProviderMock = $this->createMock(
            TokenProviderInterface::class
        );

        $this->tokenProviderMock
            ->method('validate')
            ->willReturn(
                (object) [
                    'sub' => 1,
                    'rol_id' => 2
                ]
            );

        AutenticadorMiddleware::configure(
            $this->tokenProviderMock
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_jwt_valido';
    }

    protected function tearDown(): void
    {
        Request::setTestBody(null);

        unset(
            $_SERVER['HTTP_AUTHORIZATION']
        );

        parent::tearDown();
    }

    public function test_index_lista_los_logs_correctamente(): void
    {
        $logs = collect([]);

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn($logs);

        ob_start();

        try {
            $this->controller->index();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            'Lista de logs obtenida correctamente',
            $response['message']
        );

        $this->assertSame(
            200,
            http_response_code()
        );
    }

    public function test_index_requiere_token(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $this->service
            ->expects($this->never())
            ->method('listar');

        $this->expectException(
            \App\Exceptions\UnauthorizedException::class
        );

        $this->expectExceptionMessage(
            'Token requerido'
        );

        $this->controller->index();
    }

    public function test_show_obtiene_un_log_correctamente(): void
    {
        $log = [
            'id' => 1,
            'usuario_id' => 1,
            'accion' => 'Inicio de sesión',
            'ip_address' => '127.0.0.1',
            'fecha' => '2026-09-14 10:00:00',
            'usuario_nombre' => 'Mariano Fernández',
            'usuario_email' => 'mariano@example.com',
        ];

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($log);

        ob_start();

        try {
            $this->controller->show(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            $log,
            $response['data']
        );

        $this->assertSame(
            200,
            http_response_code()
        );
    }

    public function test_show_requiere_token(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $this->service
            ->expects($this->never())
            ->method('obtener');

        $this->expectException(
            \App\Exceptions\UnauthorizedException::class
        );

        $this->expectExceptionMessage(
            'Token requerido'
        );

        $this->controller->show(1);
    }

    public function test_show_devuelve_not_found_si_el_log_no_existe(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(
                new NotFoundException('Log no encontrado')
            );

        $this->expectException(
            NotFoundException::class
        );

        $this->expectExceptionMessage(
            'Log no encontrado'
        );

        ob_start();

        try {
            $this->controller->show(999);
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }
}