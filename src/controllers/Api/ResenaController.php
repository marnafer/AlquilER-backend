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

        if (isset($_GET['solo_eliminados'])) {
            $filtros['solo_eliminados'] = filter_var(
                $_GET['solo_eliminados'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if (isset($_GET['incluir_eliminados'])) {
            $filtros['incluir_eliminados'] = filter_var(
                $_GET['incluir_eliminados'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        Response::success(
            $this->service->listar($filtros),
            200,
            'Reseñas obtenidas correctamente'
        );
    }

    // GET /api/resenas/{id}
    public function show($id)
    {
        Response::success(
            $this->service->obtener($id),
            200,
            'Reseña encontrada'
        );
    }

    // GET /api/resenas/reserva/{reservaId}
    public function getByReserva($reservaId)
    {
        Response::success(
            $this->service->obtenerPorReserva($reservaId),
            200,
            'Reseñas de la reserva obtenidas'
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
        ], 200, 'Reseñas de la propiedad obtenidas');
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
        ], 200, 'Reseñas del usuario obtenidas');
    }

    // GET /api/resenas/calificador/{calificadorId}
    public function getByCalificador($calificadorId)
    {
        Response::success(
            $this->service->obtenerPorCalificador($calificadorId),
            200,
            'Reseñas del calificador obtenidas'
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

    // PUT /api/resenas/{id}
    public function update($id)
    {
        $usuario = AutenticadorMiddleware::verificar();

        $this->service->actualizar(
            (int) $id,
            Request::json(),
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        Response::success(
            null,
            200,
            'Reseña actualizada correctamente'
        );
    }

    // DELETE /api/resenas/{id}
    public function delete($id)
    {
        $usuario = AutenticadorMiddleware::verificar();

        $this->service->eliminar(
            (int) $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        Response::success(
            [],
            200,
            'Reseña eliminada exitosamente'
        );
    }

    // POST /api/resenas/{id}/restaurar
    public function restore($id)
    {
        $usuario = AutenticadorMiddleware::verificar();

        $this->service->restaurar(
            (int) $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        Response::success(
            null,
            200,
            'Reseña restaurada exitosamente'
        );
    }
}