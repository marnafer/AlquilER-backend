<?php

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\ProvinciaService;

class ProvinciaController
{
    private readonly ProvinciaService $service;

    public function __construct(ProvinciaService $service)
    {
        $this->service = $service;
    }

    // GET /api/provincias
    public function index()
    {
        Response::success(
            $this->service->listar($this->filtrosPapelera())
        );
    }

    private function filtrosPapelera(): array
    {
        $filtros = [];

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

        return $filtros;
    }

    // GET /api/provincias/{id}
    public function show($id)
    {
        Response::success(
            $this->service->obtener($id)
        );
    }

    // POST /api/provincias
    public function store()
    {
        AutenticadorMiddleware::soloAdmin();

        $provincia = $this->service->crear(
            Request::json()
        );

        Response::created(
            $provincia,
            'Provincia creada exitosamente'
        );
    }

    // PUT /api/provincias/{id}
    public function update($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->actualizar(
            $id,
            Request::json()
        );

        Response::success(
            [],
            200,
            'Provincia actualizada exitosamente'
        );
    }

    // DELETE /api/provincias/{id}
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

    // POST /api/provincias/{id}/restaurar
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