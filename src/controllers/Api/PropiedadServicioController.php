<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Services\PropiedadServicioService;
use App\Middlewares\AutenticadorMiddleware;

class PropiedadServicioController
{
    private PropiedadServicioService $service;
    
    public function __construct(PropiedadServicioService $service)
    {
        $this->service = $service;
    }
    
    /**
     * GET /api/propiedades/{id}/servicios
     * Obtener servicios de una propiedad
     */
    public function index($request, $propiedadId)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $servicios = $this->service->obtenerServiciosPorPropiedad((int)$propiedadId);
            
            Response::success([
                'items' => $servicios,
                'total' => count($servicios)
            ], 200, 'Servicios de la propiedad obtenidos');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 404) {
                Response::notFound($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        }
    }
    
    /**
     * GET /api/servicios/{id}/propiedades
     * Obtener propiedades de un servicio (endpoint adicional)
     */
    public function getPropiedadesByServicio($request, $servicioId)
    {
        try {
            $propiedades = $this->service->obtenerPropiedadesPorServicio((int)$servicioId);
            
            Response::success([
                'items' => $propiedades,
                'total' => count($propiedades)
            ], 200, 'Propiedades del servicio obtenidas');
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 404) {
                Response::notFound($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        }
    }
    
    /**
     * POST /api/propiedades/{id}/servicios
     * Asignar un servicio a una propiedad
     * Body: { servicio_id: 1 }
     */
    public function store($request, $propiedadId)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!is_array($data)) {
                throw new \Exception("JSON inválido", 400);
            }
            
            if (empty($data['servicio_id'])) {
                throw new \Exception("El campo servicio_id es requerido", 400);
            }
            
            $asignado = $this->service->asignarServicio(
                (int)$propiedadId,
                (int)$data['servicio_id'],
                $user->sub
            );
            
            if ($asignado) {
                Response::created(
                    [
                        'propiedad_id' => (int)$propiedadId,
                        'servicio_id' => (int)$data['servicio_id']
                    ],
                    'Servicio asignado correctamente'
                );
            } else {
                Response::json([
                    'success' => false,
                    'error' => 'La propiedad ya tiene este servicio asignado'
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
     * POST /api/propiedades/{id}/servicios/multiple
     * Asignar múltiples servicios a una propiedad
     * Body: { servicio_ids: [1, 2, 3] }
     */
    public function storeMultiple($request, $propiedadId)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!is_array($data)) {
                throw new \Exception("JSON inválido", 400);
            }
            
            if (empty($data['servicio_ids']) || !is_array($data['servicio_ids'])) {
                throw new \Exception("El campo servicio_ids es requerido y debe ser un array", 400);
            }
            
            $resultados = $this->service->asignarMultiplesServicios(
                (int)$propiedadId,
                $data['servicio_ids'],
                $user->sub
            );
            
            Response::success(
                $resultados,
                200,
                'Servicios asignados correctamente'
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
     * PUT /api/propiedades/{id}/servicios
     * Sincronizar servicios (reemplaza todos)
     * Body: { servicio_ids: [1, 2, 3] }
     */
    public function update($request, $propiedadId)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!is_array($data)) {
                throw new \Exception("JSON inválido", 400);
            }
            
            if (!isset($data['servicio_ids']) || !is_array($data['servicio_ids'])) {
                throw new \Exception("El campo servicio_ids es requerido y debe ser un array", 400);
            }
            
            $resultados = $this->service->sincronizarServicios(
                (int)$propiedadId,
                $data['servicio_ids'],
                $user->sub
            );
            
            Response::success(
                $resultados,
                200,
                'Servicios sincronizados correctamente'
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
     * DELETE /api/propiedades/{id}/servicios/{servicio_id}
     * Desasignar un servicio de una propiedad
     */
    public function delete($request, $propiedadId, $servicioId)
    {
        try {
            $user = AutenticadorMiddleware::verificar();
            
            $this->service->desasignarServicio(
                (int)$propiedadId,
                (int)$servicioId,
                $user->sub
            );
            
            Response::success(
                null,
                200,
                'Servicio desasignado correctamente'
            );
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($status === 404) {
                Response::notFound($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        }
    }
}