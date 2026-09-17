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
<<<<<<< HEAD
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $filtros = [];
            
            if (isset($_GET['tipo'])) {
                $filtros['tipo'] = $_GET['tipo'];
            }
            if (isset($_GET['calificacion'])) {
                $filtros['calificacion'] = (int)$_GET['calificacion'];
            }
            if (isset($_GET['calificacion_min'])) {
                $filtros['calificacion_min'] = (int)$_GET['calificacion_min'];
            }
            if (isset($_GET['calificacion_max'])) {
                $filtros['calificacion_max'] = (int)$_GET['calificacion_max'];
            }
            if (isset($_GET['calificado_id'])) {
                $filtros['calificado_id'] = (int)$_GET['calificado_id'];
            }
            if (isset($_GET['calificador_id'])) {
                $filtros['calificador_id'] = (int)$_GET['calificador_id'];
            }
            if (isset($_GET['reserva_id'])) {
                $filtros['reserva_id'] = (int)$_GET['reserva_id'];
            }
            if (isset($_GET['propiedad_id'])) {
                $filtros['propiedad_id'] = (int)$_GET['propiedad_id'];
            }
            if (isset($_GET['usuario_id'])) {
                if ($user->rol_id != 2 && $user->sub != (int)$_GET['usuario_id']) {
                    throw new \Exception("No autorizado", 403);
                }
                $filtros['usuario_id'] = (int)$_GET['usuario_id'];
            }
            if (isset($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            }
            if (isset($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            }
            if (isset($_GET['incluir_eliminados']) && $_GET['incluir_eliminados'] === 'true') {
                if ($user->rol_id != 2) {
                    throw new \Exception("No autorizado", 403);
                }
                $filtros['incluir_eliminados'] = true;
            }
            if (isset($_GET['solo_eliminados']) && $_GET['solo_eliminados'] === 'true') {
                if ($user->rol_id != 2) {
                    throw new \Exception("No autorizado", 403);
                }
                $filtros['solo_eliminados'] = true;
            }
            
            $resenas = $this->service->listarResenas($filtros);
            
            Response::success([
                'items' => $resenas,
                'total' => count($resenas)
            ], 200, 'Reseñas obtenidas correctamente');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json(['success' => false, 'error' => $e->getMessage()], $status);
        }
    }
    
    public function show($request, $id)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $resena = $this->service->obtenerResena((int)$id);
            
            if ($user->rol_id != 2 && 
                $resena['calificador_id'] != $user->sub && 
                $resena['calificado_id'] != $user->sub) {
                throw new \Exception("No autorizado", 403);
            }
            
            Response::success([
                'resena' => $resena
            ], 200, 'Reseña encontrada');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 404) {
                Response::notFound($e->getMessage());
            } elseif ($status === 403) {
                Response::forbidden($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
=======
        $filtros = [];

        if (isset($_GET['tipo'])) {
            $filtros['tipo'] = $_GET['tipo'];
        }

        if (isset($_GET['calificacion'])) {
            $filtros['calificacion'] = (int) $_GET['calificacion'];
>>>>>>> fba7cc3816277d17ecfffab65957c9e1cd3c7f5d
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