<?php

namespace App\Controllers\Api;

use App\Services\ServicioService;
use App\Helpers\Response;
use App\Helpers\Request;
use App\Middlewares\AutenticadorMiddleware;

class ServicioController
{

    private readonly ServicioService $service;

    public function __construct(ServicioService $service)
    {
        $this->service = $service;
    }

    // GET /api/servicios
    public function index()
    {
        Response::success(
            $this->service->listar()
        );

    }

    // GET /api/servicios/{id}
    public function show($id)
    {
        Response::success(
            $this->service->obtener($id)
        );
    }

    // POST /api/servicios
    public function store()
    {
           
        AutenticadorMiddleware::soloAdmin();
        
        $servicio = $this->service->crear(Request::json());
    
        Response::created(
            $servicio,
            'Servicio creado exitosamente'
        );

    }

    // PUT /api/servicios/{id}
    public function update($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->actualizar($id, Request::json());

        Response::success(
            [],
            200,
            'Servicio actualizado exitosamente'
         );
    }

    // DELETE /api/servicios/{id}
    public function delete($id)
    {
            
        AutenticadorMiddleware::soloAdmin();

        $this->service->eliminar($id);

        Response::success(
             [],
            200,
            'Servicio eliminado exitosamente'
         );
    }

    // POST /api/servicios/{id}/restore
    public function restore($id)
    {
            
        AutenticadorMiddleware::soloAdmin();

        $this->service->restaurar($id);

        Response::success(
            [],
            200,
            'Servicio restaurado exitosamente'
        );

    }
}