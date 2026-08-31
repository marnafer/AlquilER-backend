<?php

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\RolService;

class RolController
{
    private readonly RolService $service;

    public function __construct(RolService $service)
    {
        $this->service = $service;
    }

    // GET /api/roles
    public function index()
    {
        Response::success(
            $this->service->listar()
        );
    }

    // GET /api/roles/{id}
    public function show($id)
    {
        Response::success(
            $this->service->obtener($id)
        );
    }

    // POST /api/roles
    public function store()
    {
        AutenticadorMiddleware::soloAdmin();

        $rol = $this->service->crear(Request::json());

        Response::created(
            $rol,
            'Rol creado exitosamente'
        );
    }

    // PUT /api/roles/{id}
    public function update($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->actualizar($id, Request::json());

        Response::success(
            [],
            200,
            'Rol actualizado exitosamente'
        );
    }

    // DELETE /api/roles/{id}
    public function delete($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->eliminar($id);

        Response::success(
            [],
            200,
            'Rol eliminado exitosamente'
        );
    }

    // POST /api/roles/{id}/restaurar
    public function restore($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->restaurar($id);

        Response::success(
            [],
            200,
            'Rol restaurado exitosamente'
        );
    }
}