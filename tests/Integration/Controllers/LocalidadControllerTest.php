<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\LogActividadController;
use App\Services\LogActividadService;
use App\Exceptions\NotFoundException;

class LogActividadControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(LogActividadService::class);
        $this->controller = new LogActividadController($this->service);
    }

    public function test_index_lista_los_logs_correctamente(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn([]);

        ob_start();

        $this->controller->index();

        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString(
            'Lista de logs obtenida correctamente',
            $output
        );
    }

    public function test_index_requiere_usuario_autenticado(): void
    {
        ob_start();

        $this->controller->index();

        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);

        $this->service
            ->expects($this->never())
            ->method('listar');
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

        $this->controller->show(1);

        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    public function test_show_requiere_usuario_autenticado(): void
    {
        ob_start();

        $this->controller->show(1);

        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);

        $this->service
            ->expects($this->never())
            ->method('obtener');
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

        ob_start();

        $this->controller->show(999);

        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('404', $output);
        $this->assertStringContainsString(
            'Log no encontrado',
            $output
        );
    }
}