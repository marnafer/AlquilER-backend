<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Services\ConsultaService;
use App\Middlewares\AutenticadorMiddleware;

class ConsultaController
{
    private ConsultaService $service;
    
    public function __construct(ConsultaService $service)
    {
        $this->service = $service;
    }
    
    /**
     * GET /api/consultas
     * Listar todas las consultas (solo admin)
     */
    public function index($request)
    {
        try {
            AutenticadorMiddleware::soloAdmin();
            
            $filtros = [];
            
            if (isset($_GET['usuario_id'])) {
                $filtros['usuario_id'] = (int)$_GET['usuario_id'];
            }
            if (isset($_GET['propiedad_id'])) {
                $filtros['propiedad_id'] = (int)$_GET['propiedad_id'];
            }
            if (isset($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            }
            if (isset($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            }
            if (isset($_GET['incluir_eliminados']) && $_GET['incluir_eliminados'] === 'true') {
                $filtros['incluir_eliminados'] = true;
            }
            if (isset($_GET['solo_eliminados']) && $_GET['solo_eliminados'] === 'true') {
                $filtros['solo_eliminados'] = true;
            }
            
            $consultas = $this->service->listarConsultas($filtros);
            
            Response::success([
                'items' => $consultas,
                'total' => count($consultas)
            ], 200, 'Consultas obtenidas correctamente');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json(['success' => false, 'error' => $e->getMessage()], $status);
        }
    }
    
    /**
     * GET /api/consultas/{id}
     * Obtener una consulta por ID
     */
    public function show($request, $id)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $consulta = $this->service->obtenerConsulta((int)$id);
            
            // Verificar permisos (solo el dueño o admin)
            if ($user->rol_id != 3 && $consulta->usuario_id != $user->sub) {
                throw new \Exception("No autorizado", 403);
            }
            
            Response::success([
                'consulta' => $consulta
            ], 200, 'Consulta encontrada');
            
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
    
    /**
     * GET /api/consultas/propiedad/{propiedadId}
     * Obtener consultas de una propiedad
     */
    public function indexByPropiedad($request, $propiedadId)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $consultas = $this->service->obtenerConsultasPorPropiedad(
                (int)$propiedadId,
                $user->sub,
                $user->rol_id
            );
            
            Response::success([
                'items' => $consultas,
                'total' => count($consultas)
            ], 200, 'Consultas de la propiedad obtenidas');
            
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
    
    /**
     * GET /api/consultas/usuario/{usuarioId}
     * Obtener consultas de un usuario
     */
    public function indexByUsuario($request, $usuarioId)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $consultas = $this->service->obtenerConsultasPorUsuario(
                (int)$usuarioId,
                $user->sub,
                $user->rol_id
            );
            
            Response::success([
                'items' => $consultas,
                'total' => count($consultas)
            ], 200, 'Consultas del usuario obtenidas');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 403) {
                Response::forbidden($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        }
    }
    
    /**
     * POST /api/consultas
     * Crear una nueva consulta
     * Body: { propiedad_id }
     */
    public function store($request)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!is_array($data)) {
                throw new \Exception("JSON inválido", 400);
            }
            
            // Validar campos requeridos
            if (empty($data['propiedad_id'])) {
                throw new \Exception("El campo propiedad_id es requerido", 400);
            }
            
            // Preparar datos
            $datosConsulta = [
                'propiedad_id' => (int)$data['propiedad_id'],
                'usuario_id' => $user->sub,
                'fecha_consulta' => date('Y-m-d H:i:s')
            ];
            
            $id = $this->service->crearConsulta($datosConsulta);
            
            Response::created(
                ['id' => $id],
                'Consulta creada exitosamente'
            );
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 404) {
                Response::notFound($e->getMessage());
            } elseif ($status === 400) {
                Response::badRequest($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        }
    }
    
    /**
     * PUT /api/consultas/{id}
     * Actualizar una consulta existente
     * Body: { mensaje }
     */
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
            
            $this->service->actualizarConsulta((int)$id, $data, $user->sub);
            
            Response::success(
                null,
                200,
                'Consulta actualizada correctamente'
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
    
    /**
     * DELETE /api/consultas/{id}
     * Eliminar una consulta (solo admin)
     */
    public function delete($request, $id)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            // Solo admin puede eliminar
            AutenticadorMiddleware::soloAdmin();
            
            $this->service->eliminarConsulta((int)$id, $user->sub);
            
            Response::success(
                null,
                200,
                'Consulta eliminada exitosamente'
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