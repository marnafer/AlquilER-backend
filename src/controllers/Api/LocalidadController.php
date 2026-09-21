<?php

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\LocalidadService;

class LocalidadController
{
    private readonly LocalidadService $service;

    public function __construct(LocalidadService $service)
    {
        $this->service = $service;
    }

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

    public function show($id)
    {
        Response::success(
            $this->service->obtener($id)
        );
    }

    public function store()
    {
        AutenticadorMiddleware::soloAdmin();

        $localidad = $this->service->crear(Request::json());

        Response::created(
            $localidad,
            'Localidad creada exitosamente'
        );
    }

    public function update($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->actualizar($id, Request::json());

        Response::success(
            [],
            200,
            'Localidad actualizada exitosamente'
        );
    }

    public function delete($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->eliminar($id);

        Response::success(
            [],
            200,
            'Localidad eliminada exitosamente'
        );
    }

    public function restore($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->restaurar($id);

        Response::success(
            [],
            200,
            'Localidad restaurada exitosamente'
        );
    }
}