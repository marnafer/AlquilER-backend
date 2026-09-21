<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\LogActividadController;
use App\Exceptions\NotFoundException;
use App\Services\LogActividadService;
use Tests\TestCase;

final class LogActividadControllerTest extends TestCase
{
    private $service;
    private LogActividadController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        $this->service = $this->createMock(
            LogActividadService::class
        );

        $this->controller = new LogActividadController(
            $this->service
        );
    }

    protected function tearDown(): void
    {
        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        parent::tearDown();
    }

    public function test_index_lista_los_logs_correctamente(): void
    {
        $logs = collect([]);

        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn($logs);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            $logs->toArray(),
            $response['data']
        );

        $this->assertSame(
            'Lista de logs obtenida correctamente',
            $response['message']
        );
    }

    public function test_index_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('listar');

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertFalse($response['success']);

        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_index_requiere_ser_admin(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('listar');

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertFalse($response['success']);

        $this->assertSame(
            'Solo administradores',
            $response['error']
        );
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

        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($log);

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            $log,
            $response['data']
        );
    }

    public function test_show_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('obtener');

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertFalse($response['success']);

        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_show_requiere_ser_admin(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('obtener');

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertFalse($response['success']);

        $this->assertSame(
            'Solo administradores',
            $response['error']
        );
    }

    public function test_show_devuelve_not_found_si_el_log_no_existe(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(
                new NotFoundException('Log no encontrado')
            );

        $response = $this->captureJson(
            fn() => $this->controller->show(999)
        );

        $this->assertFalse($response['success']);

        $this->assertSame(
            'Log no encontrado',
            $response['error']
        );
    }
}