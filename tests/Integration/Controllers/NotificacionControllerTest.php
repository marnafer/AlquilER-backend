<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\NotificacionController;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Services\NotificacionService;
use Tests\TestCase;

final class NotificacionControllerTest extends TestCase
{
    private $service;
    private NotificacionController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        $this->service = $this->createMock(
            NotificacionService::class
        );

        $this->controller = new NotificacionController(
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

    public function test_index_lista_las_notificaciones_y_no_leidas(): void
    {
        $items = [
            [
                'id' => 1,
                'tipo' => 'consulta_nueva',
                'titulo' => 'Nueva consulta',
                'leeable' => false,
            ],
        ];

        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('listarPorUsuario')
            ->with(5)
            ->willReturn($items);

        $this->service
            ->expects($this->once())
            ->method('contarNoLeidas')
            ->with(5)
            ->willReturn(1);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            $items,
            $response['data']['items']
        );

        $this->assertSame(1, $response['data']['no_leidas']);
    }

    public function test_index_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('listarPorUsuario');

        $this->service
            ->expects($this->never())
            ->method('contarNoLeidas');

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertFalse($response['success']);

        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_no_leidas_es_para_cualquier_usuario_autenticado(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('contarNoLeidas')
            ->with(5)
            ->willReturn(3);

        $response = $this->captureJson(
            fn() => $this->controller->noLeidas()
        );

        $this->assertTrue($response['success']);

        $this->assertSame(3, $response['data']['no_leidas']);
    }

    public function test_no_leidas_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('contarNoLeidas');

        $response = $this->captureJson(
            fn() => $this->controller->noLeidas()
        );

        $this->assertFalse($response['success']);

        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_marcar_leida_marca_la_notificacion(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('marcarLeida')
            ->with(10, 5);

        $response = $this->captureJson(
            fn() => $this->controller->marcarLeida(10)
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            'Notificación marcada como leída',
            $response['message']
        );
    }

    public function test_marcar_leida_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('marcarLeida');

        $response = $this->captureJson(
            fn() => $this->controller->marcarLeida(10)
        );

        $this->assertFalse($response['success']);

        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_marcar_leida_devuelve_not_found_si_la_notificacion_no_existe(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('marcarLeida')
            ->with(999, 5)
            ->willThrowException(
                new NotFoundException('Notificación no encontrada')
            );

        $response = $this->captureJson(
            fn() => $this->controller->marcarLeida(999)
        );

        $this->assertFalse($response['success']);

        $this->assertSame(
            'Notificación no encontrada',
            $response['error']
        );
    }

    public function test_marcar_leida_devuelve_forbidden_si_la_notificacion_no_es_del_usuario(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('marcarLeida')
            ->with(10, 5)
            ->willThrowException(
                new ForbiddenException('No puedes marcar como leída una notificación ajena')
            );

        $response = $this->captureJson(
            fn() => $this->controller->marcarLeida(10)
        );

        $this->assertFalse($response['success']);

        $this->assertSame(
            'No puedes marcar como leída una notificación ajena',
            $response['error']
        );
    }

    public function test_marcar_todas_leidas_marca_todas_las_notificaciones(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('marcarTodasLeidas')
            ->with(5);

        $response = $this->captureJson(
            fn() => $this->controller->marcarTodasLeidas()
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            'Notificaciones marcadas como leídas',
            $response['message']
        );
    }

    public function test_marcar_todas_leidas_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('marcarTodasLeidas');

        $response = $this->captureJson(
            fn() => $this->controller->marcarTodasLeidas()
        );

        $this->assertFalse($response['success']);

        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }
}