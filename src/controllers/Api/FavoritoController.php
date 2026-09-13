<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Services\FavoritoService;

class FavoritoController
{
    private FavoritoService $service;
    
    public function __construct(FavoritoService $service)
    {
        $this->service = $service;
    }
    
    /**
     * GET /api/favoritos
     * Obtener todos los favoritos del usuario autenticado
     */
    public function index($request)
    {
        try {
            $usuarioId = $request->usuario_id ?? null;
            
            if (!$usuarioId) {
                Response::unauthorized('Usuario no autenticado');
                return;
            }
            
            $favoritos = $this->service->obtenerFavoritos((int)$usuarioId);
            Response::success($favoritos, 200, 'Favoritos obtenidos correctamente');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json(['success' => false, 'error' => $e->getMessage()], $status);
        }
    }
    
    /**
     * POST /api/favoritos
     * Agregar una propiedad a favoritos
     * Body: { "propiedad_id": 123 }
     */
    public function store($request)
    {
        try {
            $usuarioId = $request->usuario_id ?? null;
            
            if (!$usuarioId) {
                Response::unauthorized('Usuario no autenticado');
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['propiedad_id']) || !is_numeric($data['propiedad_id'])) {
                Response::badRequest('El campo propiedad_id es requerido y debe ser numérico');
                return;
            }
            
            $propiedadId = (int)$data['propiedad_id'];
            $agregado = $this->service->agregarFavorito((int)$usuarioId, $propiedadId);
            
            if ($agregado) {
                Response::created(
                    [
                        'propiedad_id' => $propiedadId,
                        'es_favorito' => true
                    ],
                    'Propiedad agregada a favoritos'
                );
            } else {
                Response::json([
                    'success' => false,
                    'error' => 'La propiedad ya está en favoritos'
                ], 409);
            }
            
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
     * GET /api/usuarios/{id}/favoritos
     * Obtener favoritos de un usuario específico
     */
    public function indexByUsuario($request, $id)
    {
        try {
            $usuarioId = (int)$id;
            
            if ($usuarioId <= 0) {
                Response::badRequest('ID de usuario inválido');
                return;
            }
            
            $favoritos = $this->service->obtenerFavoritos($usuarioId);
            Response::success($favoritos, 200, 'Favoritos del usuario obtenidos correctamente');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json(['success' => false, 'error' => $e->getMessage()], $status);
        }
    }
    
    /**
     * DELETE /api/favoritos/propiedad/{propiedad_id}
     * Eliminar una propiedad de favoritos
     */
    public function deleteByPropiedad($request, $propiedadId)
    {
        try {
            $usuarioId = $request->usuario_id ?? null;
            
            if (!$usuarioId) {
                Response::unauthorized('Usuario no autenticado');
                return;
            }
            
            if (!is_numeric($propiedadId) || $propiedadId <= 0) {
                Response::badRequest('ID de propiedad inválido');
                return;
            }
            
            $this->service->eliminarFavorito((int)$usuarioId, (int)$propiedadId);
            Response::success(null, 200, 'Propiedad eliminada de favoritos');
            
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
     * Alias para index() - Métodos en español para compatibilidad con tests
     */
    public function listar($request)
    {
        return $this->index($request);
    }

    /**
     * Alias para store()
     */
    public function crear($request)
    {
        return $this->store($request);
    }

    /**
     * Alias para indexByUsuario()
     */
    public function listarPorUsuario($request, $id)
    {
        return $this->indexByUsuario($request, $id);
    }

    /**
     * Alias para deleteByPropiedad()
     */
    public function eliminarPorPropiedad($request, $propiedadId)
    {
        return $this->deleteByPropiedad($request, $propiedadId);
    }

    /**
     * Eliminar un favorito específico por propiedad
     */
    public function eliminar($request, $propiedadId)
    {
        return $this->deleteByPropiedad($request, $propiedadId);
    }

    /**
     * Verificar si una propiedad es favorita del usuario
     */
    public function verificar($request, $propiedadId)
    {
        try {
            $usuarioId = $request->usuario_id ?? null;
            
            if (!$usuarioId) {
                Response::unauthorized('Usuario no autenticado');
                return;
            }
            
            if (!is_numeric($propiedadId) || $propiedadId <= 0) {
                Response::badRequest('ID de propiedad inválido');
                return;
            }
            
            $esFavorito = $this->service->esFavorito((int)$usuarioId, (int)$propiedadId);
            
            Response::success(
                ['es_favorito' => $esFavorito],
                200,
                'Verificación realizada'
            );
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json(['success' => false, 'error' => $e->getMessage()], $status);
        }
    }
}