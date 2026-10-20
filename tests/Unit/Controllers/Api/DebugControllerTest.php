<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\DebugController;
use Tests\TestCase;

/**
 * DebugController usa Eloquent de fondo, así que solo se prueban las rutas
 * que no tocan la base de datos ni los modelos.
 */
final class DebugControllerTest extends TestCase
{
    private DebugController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new DebugController();
    }

    public function test_logs_devuelve_un_arreglo(): void
    {
        $response = $this->controller->logs($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertIsArray($response->payload()['data']);
    }

    public function test_logs_no_requiere_autenticacion(): void
    {
        $response = $this->controller->logs($this->makeRequest());

        $this->assertSame(200, $response->status());
    }

    public function test_clear_log_devuelve_200(): void
    {
        $response = $this->controller->clearLog($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertContains(
            $response->payload()['message'],
            ['Log limpiado exitosamente', 'No existe archivo de log para limpiar']
        );
    }

    public function test_clear_log_no_requiere_autenticacion(): void
    {
        $response = $this->controller->clearLog($this->makeRequest());

        $this->assertSame(200, $response->status());
    }

    public function test_phpinfo_devuelve_el_html(): void
    {
        $response = $this->controller->phpinfo($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertArrayHasKey('phpinfo', $response->payload());
        $this->assertNotEmpty($response->payload()['phpinfo']);
    }
}
