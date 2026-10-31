<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\NotificacionService;

class NotificacionController
{
    public function __construct(
        private readonly NotificacionService $service,
        private readonly Authenticator $auth
    ) {
    }

    // GET /api/notificaciones
    public function index(Request $request): Response
    {
        $usuario = $this->auth->verificar($request);

        return Response::success([
            'items' => $this->service->listarPorUsuario(
                (int) $usuario->sub
            ),
            'no_leidas' => $this->service->contarNoLeidas(
                (int) $usuario->sub
            )
        ]);
    }

    // GET /api/notificaciones/no-leidas
    public function noLeidas(Request $request): Response
    {
        $usuario = $this->auth->verificar($request);

        return Response::success([
            'no_leidas' => $this->service->contarNoLeidas(
                (int) $usuario->sub
            )
        ]);
    }

    // PUT /api/notificaciones/{id}/leer
    public function marcarLeida(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->marcarLeida(
            $id,
            (int) $usuario->sub
        );

        return Response::success(
            [],
            200,
            'Notificación marcada como leída'
        );
    }

    // PUT /api/notificaciones/leer-todas
    public function marcarTodasLeidas(Request $request): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->marcarTodasLeidas(
            (int) $usuario->sub
        );

        return Response::success(
            [],
            200,
            'Notificaciones marcadas como leídas'
        );
    }
}
