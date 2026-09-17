<?php

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\ReservaService;

class ReservaController
{
    private readonly ReservaService $service;

    public function __construct(ReservaService $service)
    {
        $this->service = $service;
    }

    // GET /api/reservas
    public function index()
    {
        $usuario = AutenticadorMiddleware::verificar();

        $filtros = [];

        if (isset($_GET['estado'])) {
            $filtros['estado'] = $_GET['estado'];
        }

        if (isset($_GET['usuario_id'])) {
            $filtros['usuario_id'] = (int) $_GET['usuario_id'];
        }

        if (isset($_GET['propiedad_id'])) {
            $filtros['propiedad_id'] = (int) $_GET['propiedad_id'];
        }

        Response::success(
            $this->service->listar(
                (int) $usuario->sub,
                (int) $usuario->rol_id,
                $filtros
            )
        );
    }

    // GET /api/reservas/{id}
    public function show($id)
    {
        $usuario = AutenticadorMiddleware::verificar();

        Response::success(
            $this->service->obtener(
                $id,
                (int) $usuario->sub,
                (int) $usuario->rol_id
            )
        );
    }

    // POST /api/reservas
    public function store()
    {
        $usuario = AutenticadorMiddleware::verificar();

        $id = $this->service->crear(
            Request::json(),
            (int) $usuario->sub
        );

        Response::created(
            ['id' => $id],
            'Reserva creada correctamente'
        );
    }

    // PUT /api/reservas/{id}/confirmar
    public function confirm($id)
    {
        $usuario = AutenticadorMiddleware::verificar();

        $this->service->confirmar(
            $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        Response::success(
            [],
            200,
            'Reserva confirmada correctamente'
        );
    }

    // PUT /api/reservas/{id}/rechazar
    public function reject($id)
    {
        $usuario = AutenticadorMiddleware::verificar();

        $this->service->rechazar(
            $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        Response::success(
            [],
            200,
            'Reserva rechazada correctamente'
        );
    }

    // PUT /api/reservas/{id}
    public function update($id)
    {
        $usuario = AutenticadorMiddleware::verificar();

        $this->service->actualizar(
            $id,
            Request::json(),
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        Response::success(
            [],
            200,
            'Reserva actualizada correctamente'
        );
    }

    // DELETE /api/reservas/{id}
    public function delete($id)
    {
        $usuario = AutenticadorMiddleware::verificar();

        $this->service->eliminar(
            $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        Response::success(
            [],
            200,
            'Reserva eliminada correctamente'
        );
    }

    // POST /api/reservas/{id}/restore
    public function restore($id)
    {
        $usuario = AutenticadorMiddleware::verificar();

        $this->service->restaurar(
            $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        Response::success(
            [],
            200,
            'Reserva restaurada correctamente'
        );
    }
}