<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\NotificacionController;
use App\Services\NotificacionService;
use Tests\TestCase;

final class NotificacionControllerTest extends TestCase
{
    private NotificacionService $service;
    private NotificacionController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(NotificacionService::class);
        $this->controller = new NotificacionController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );
    }

    public function test_index_devuelve_items_y_no_leidas(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listarPorUsuario')
            ->with(5)
            ->willReturn([['id' => 1]]);

        $this->service
            ->expects($this->once())
            ->method('contarNoLeidas')
            ->with(5)
            ->willReturn(2);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame([['id' => 1]], $response->payload()['data']['items']);
        $this->assertSame(2, $response->payload()['data']['no_leidas']);
    }

    public function test_no_leidas_devuelve_el_contador(): void
    {
        $this->service
            ->expects($this->once())
            ->method('contarNoLeidas')
            ->with(5)
            ->willReturn(7);

        $response = $this->controller->noLeidas($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame(7, $response->payload()['data']['no_leidas']);
    }

    public function test_marcar_leida_pasa_el_id_y_el_usuario(): void
    {
        $this->service
            ->expects($this->once())
            ->method('marcarLeida')
            ->with(9, 5);

        $response = $this->controller->marcarLeida($this->makeRequest(), 9);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Notificación marcada como leída',
            $response->payload()['message']
        );
    }

    public function test_marcar_todas_leidas(): void
    {
        $this->service
            ->expects($this->once())
            ->method('marcarTodasLeidas')
            ->with(5);

        $response = $this->controller->marcarTodasLeidas(
            $this->makeRequest()
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Notificaciones marcadas como leídas',
            $response->payload()['message']
        );
    }

    public function test_requiere_autenticacion(): void
    {
        $controller = new NotificacionController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('listarPorUsuario');

        $this->expectException(\App\Exceptions\UnauthorizedException::class);

        $controller->index($this->makeRequest());
    }
}
