<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\LogActividadController;
use App\Services\LogActividadService;
use Tests\TestCase;

final class LogActividadControllerTest extends TestCase
{
    private LogActividadService $service;
    private LogActividadController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(LogActividadService::class);
        $this->controller = new LogActividadController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 2))
        );
    }

    public function test_index_lista_los_logs_como_admin(): void
    {
        $logs = new \Illuminate\Support\Collection([
            ['id' => 1, 'accion' => 'login'],
        ]);

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn($logs);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame($logs, $response->payload()['data']);
        $this->assertSame(
            'Lista de logs obtenida correctamente',
            $response->payload()['message']
        );
    }

    public function test_index_rechaza_a_un_no_administrador(): void
    {
        $controller = new LogActividadController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->index($this->makeRequest());
    }

    public function test_index_sin_token_rechaza(): void
    {
        $controller = new LogActividadController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(\App\Exceptions\UnauthorizedException::class);

        $controller->index($this->makeRequest());
    }

    public function test_show_devuelve_un_log(): void
    {
        $log = ['id' => 3, 'accion' => 'crear'];

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(3)
            ->willReturn($log);

        $response = $this->controller->show($this->makeRequest(), 3);

        $this->assertSame(200, $response->status());
        $this->assertSame($log, $response->payload()['data']);
    }

    public function test_show_rechaza_a_un_no_administrador(): void
    {
        $controller = new LogActividadController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('obtener');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->show($this->makeRequest(), 3);
    }
}
