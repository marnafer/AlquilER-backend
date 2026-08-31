<?php

namespace App\Controllers\Api;

use App\Services\ProvinciaService;
use App\Helpers\Response;
use App\Helpers\Request;
use App\Middlewares\AutenticadorMiddleware;

class ProvinciaController
{

    private readonly ProvinciaService $service;

    public function __construct(ProvinciaService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/provincias
     */
    public function index()
    {
        Response::success(
            $this->service->listar()
        );
    }

    /**
     * GET /api/provincias/{id}
     */
    public function show($id)
    {
        Response::success(
            $this->service->obtener($id)
        );
    }

    /**
     * POST /api/provincias
     */
    public function store()
    { 
        AutenticadorMiddleware::soloAdmin();

        $provincia = $this->service->crear(Request::json());

        Response::created(
            $provincia,
            'Provincia creada exitosamente'
        );
    }

    /**
     * PUT /api/provincias/{id}
     */
    public function update($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->actualizar($id, Request::json());

        Response::success(
            [],
             200,
            'Provincia actualizada exitosamente'
         );

    }

    /**
     * DELETE /api/provincias/{id}
     */
    public function delete($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->eliminar($id);

        Response::success(
            [],
            200,
            'Provincia eliminada exitosamente'
        );
    }

    /**
     * POST /api/provincias/{id}/restaurar
     */
    public function restore($id)
    {
        AutenticadorMiddleware::soloAdmin();

       $this->service->restaurar($id);

       Response::success(
            [],
            200,
            'Provincia restaurada exitosamente'
        );
    }
}