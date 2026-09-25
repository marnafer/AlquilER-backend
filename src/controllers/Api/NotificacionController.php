<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\NotificacionService;

class NotificacionController
{
    private readonly NotificacionService $service;

    public function __construct(NotificacionService $service)
    {
        $this->service = $service;
    }

    // GET /api/notificaciones
    public function index()
    {
        $usuario = AutenticadorMiddleware::verificar();

        Response::success([
            'items' => $this->service->listarPorUsuario(
                (int) $usuario->sub
            ),
            'no_leidas' => $this->service->contarNoLeidas(
                (int) $usuario->sub
            )
        ]);
    }

    // GET /api/notificaciones/no-leidas
    public function noLeidas()
    {
        $usuario = AutenticadorMiddleware::verificar();

        Response::success([
            'no_leidas' => $this->service->contarNoLeidas(
                (int) $usuario->sub
            )
        ]);
    }

    // PUT /api/notificaciones/{id}/leer
    public function marcarLeida($id)
    {
        $usuario = AutenticadorMiddleware::verificar();

        $this->service->marcarLeida(
            $id,
            (int) $usuario->sub
        );

        Response::success(
            [],
            200,
            'Notificación marcada como leída'
        );
    }

    // PUT /api/notificaciones/leer-todas
    public function marcarTodasLeidas()
    {
        $usuario = AutenticadorMiddleware::verificar();

        $this->service->marcarTodasLeidas(
            (int) $usuario->sub
        );

        Response::success(
            [],
            200,
            'Notificaciones marcadas como leídas'
        );
    }
}