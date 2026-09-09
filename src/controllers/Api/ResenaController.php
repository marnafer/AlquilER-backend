<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Services\ResenaService;
use App\Middlewares\AutenticadorMiddleware;

class ResenaController
{
    private ResenaService $service;
    
    public function __construct(ResenaService $service)
    {
        $this->service = $service;
    }
    
    public function index($request)
    {
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
                if ($user->rol_id != 3 && $user->sub != (int)$_GET['usuario_id']) {
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
                if ($user->rol_id != 3) {
                    throw new \Exception("No autorizado", 403);
                }
                $filtros['incluir_eliminados'] = true;
            }
            if (isset($_GET['solo_eliminados']) && $_GET['solo_eliminados'] === 'true') {
                if ($user->rol_id != 3) {
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
            
            if ($user->rol_id != 3 && 
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
        }
    }
    
    public function getByReserva($request, $reservaId)
    {
        try {
            $resenas = $this->service->obtenerResenasPorReserva((int)$reservaId);
            
            Response::success([
                'items' => $resenas,
                'total' => count($resenas)
            ], 200, 'Reseñas de la reserva obtenidas');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json(['success' => false, 'error' => $e->getMessage()], $status);
        }
    }
    
    public function getByPropiedad($request, $propiedadId)
    {
        try {
            $resenas = $this->service->obtenerResenasPorPropiedad((int)$propiedadId);
            
            Response::success([
                'items' => $resenas,
                'total' => count($resenas),
                'promedio' => $this->service->obtenerPromedioPropiedad((int)$propiedadId)
            ], 200, 'Reseñas de la propiedad obtenidas');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 404) {
                Response::notFound($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        }
    }
    
    public function getByUsuario($request, $usuarioId)
    {
        try {
            $resenas = $this->service->obtenerResenasPorUsuario((int)$usuarioId);
            
            Response::success([
                'items' => $resenas,
                'total' => count($resenas),
                'promedio' => $this->service->obtenerPromedioUsuario((int)$usuarioId)
            ], 200, 'Reseñas del usuario obtenidas');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 404) {
                Response::notFound($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        }
    }
    
    public function getByCalificador($request, $calificadorId)
    {
        try {
            $resenas = $this->service->obtenerResenasPorCalificador((int)$calificadorId);
            
            Response::success([
                'items' => $resenas,
                'total' => count($resenas)
            ], 200, 'Reseñas del calificador obtenidas');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 404) {
                Response::notFound($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        }
    }
    
    public function store($request)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!is_array($data)) {
                throw new \Exception("JSON inválido", 400);
            }
            
            $camposRequeridos = ['reserva_id', 'tipo', 'calificacion'];
            $errores = [];
            
            foreach ($camposRequeridos as $campo) {
                if (!isset($data[$campo]) || $data[$campo] === '') {
                    $errores[] = "El campo '{$campo}' es requerido";
                }
            }
            
            if (!empty($errores)) {
                throw new \Exception(implode(', ', $errores), 400);
            }
            
            $datosResena = [
                'reserva_id' => (int)$data['reserva_id'],
                'tipo' => $data['tipo'],
                'calificacion' => (int)$data['calificacion'],
                'comentario' => $data['comentario'] ?? null
            ];
            
            $id = $this->service->crearResena($datosResena);
            
            Response::created(
                ['id' => $id],
                'Reseña creada exitosamente'
            );
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 404) {
                Response::notFound($e->getMessage());
            } elseif ($status === 400) {
                Response::badRequest($e->getMessage());
            } elseif ($status === 409) {
                Response::json(['success' => false, 'error' => $e->getMessage()], 409);
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        }
    }
    
    public function update($request, $id)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!is_array($data)) {
                throw new \Exception("JSON inválido", 400);
            }
            
            if (empty($data)) {
                throw new \Exception("No hay datos para actualizar", 400);
            }
            
            $this->service->actualizarResena((int)$id, $data, $user->sub);
            
            Response::success(
                null,
                200,
                'Reseña actualizada correctamente'
            );
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 404) {
                Response::notFound($e->getMessage());
            } elseif ($status === 403) {
                Response::forbidden($e->getMessage());
            } elseif ($status === 400) {
                Response::badRequest($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        }
    }
    
    public function delete($request, $id)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $this->service->eliminarResena((int)$id, $user->sub);
            
            Response::success(
                null,
                200,
                'Reseña eliminada exitosamente'
            );
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 404) {
                Response::notFound($e->getMessage());
            } elseif ($status === 403) {
                Response::forbidden($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        }
    }
}