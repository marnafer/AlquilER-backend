<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\LogActividadService;

class LogActividadController
{
    public function __construct(
        private readonly LogActividadService $service
    ) {
    }

    /**
     * GET /api/logs-actividad
     */
    public function index()
    {
        AutenticadorMiddleware::soloAdmin();

        Response::success(
            $this->service->listar(),
            200,
            'Lista de logs obtenida correctamente'
        );
    }

    /**
     * GET /api/logs-actividad/{id}
     */
    public function show($id)
    {
        AutenticadorMiddleware::soloAdmin();

        Response::success(
            $this->service->obtener($id)
        );
    }
}
