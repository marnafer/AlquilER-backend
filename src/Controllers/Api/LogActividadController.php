<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\LogActividadService;

class LogActividadController
{
    public function __construct(
        private readonly LogActividadService $service,
        private readonly Authenticator $auth
    ) {
    }

    /**
     * GET /api/logs-actividad
     */
    public function index(Request $request): Response
    {
        $this->auth->soloAdmin($request);

        return Response::success(
            $this->service->listar(),
            200,
            'Lista de logs obtenida correctamente'
        );
    }

    /**
     * GET /api/logs-actividad/{id}
     */
    public function show(Request $request, $id): Response
    {
        $this->auth->soloAdmin($request);

        return Response::success(
            $this->service->obtener($id)
        );
    }
}
