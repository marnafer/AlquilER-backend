<?php

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\ResenaService;

class ResenaController
{
    private readonly ResenaService $service;

    public function __construct(ResenaService $service)
    {
        $this->service = $service;
    }

    // GET /api/resenas
    public function index()
    {
        $filtros = [];

        if (isset($_GET['tipo'])) {
            $filtros['tipo'] = $_GET['tipo'];
        }

        if (isset($_GET['calificacion'])) {
            $filtros['calificacion'] = (int) $_GET['calificacion'];
        }

        if (isset($_GET['calificacion_min'])) {
            $filtros['calificacion_min'] = (int) $_GET['calificacion_min'];
        }

        if (isset($_GET['calificacion_max'])) {
            $filtros['calificacion_max'] = (int) $_GET['calificacion_max'];
        }

        if (isset($_GET['calificador_id'])) {
            $filtros['calificador_id'] = (int) $_GET['calificador_id'];
        }

        if (isset($_GET['reserva_id'])) {
            $filtros['reserva_id'] = (int) $_GET['reserva_id'];
        }

        if (isset($_GET['propiedad_id'])) {
            $filtros['propiedad_id'] = (int) $_GET['propiedad_id'];
        }

        if (isset($_GET['usuario_id'])) {
            $filtros['usuario_id'] = (int) $_GET['usuario_id'];
        }

        if (isset($_GET['fecha_desde'])) {
            $filtros['fecha_desde'] = $_GET['fecha_desde'];
        }

        if (isset($_GET['fecha_hasta'])) {
            $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
        }

        Response::success(
            $this->service->listar($filtros)
        );
    }

    // GET /api/resenas/{id}
    public function show($id)
    {
        Response::success(
            $this->service->obtener($id)
        );
    }

    // GET /api/resenas/reserva/{reservaId}
    public function getByReserva($reservaId)
    {
        Response::success(
            $this->service->obtenerPorReserva($reservaId)
        );
    }

    // GET /api/resenas/propiedad/{propiedadId}
    public function getByPropiedad($propiedadId)
    {
        $resenas = $this->service->obtenerPorPropiedad(
            $propiedadId
        );

        $promedio = $this->service->promedioPropiedad(
            $propiedadId
        );

        Response::success([
            'items' => $resenas,
            'total' => $resenas->count(),
            'promedio' => $promedio,
        ]);
    }

    // GET /api/resenas/usuario/{usuarioId}
    public function getByUsuario($usuarioId)
    {
        $resenas = $this->service->obtenerPorUsuario(
            $usuarioId
        );

        $promedio = $this->service->promedioUsuario(
            $usuarioId
        );

        Response::success([
            'items' => $resenas,
            'total' => $resenas->count(),
            'promedio' => $promedio,
        ]);
    }

    // GET /api/resenas/calificador/{calificadorId}
    public function getByCalificador($calificadorId)
    {
        Response::success(
            $this->service->obtenerPorCalificador(
                $calificadorId
            )
        );
    }

    // POST /api/resenas
    public function store()
    {
        $usuario = AutenticadorMiddleware::verificar();

        $resena = $this->service->crear(
            Request::json(),
            (int) $usuario->sub
        );

        Response::created(
            $resena,
            'Reseña creada exitosamente'
        );
    }

    // DELETE /api/resenas/{id}
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
            'Reseña eliminada exitosamente'
        );
    }
}