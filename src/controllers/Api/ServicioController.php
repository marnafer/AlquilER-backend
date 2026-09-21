<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\ServicioService;

class ServicioController
{
    public function __construct(
        private readonly ServicioService $service
    ) {
    }

    public function index(): void
    {
        Response::success(
            $this->service->listar()
        );
    }

    public function show($id): void
    {
        Response::success(
            $this->service->obtener($id)
        );
    }

    public function store(): void
    {
        AutenticadorMiddleware::soloAdmin();

        $servicio = $this->service->crear(
            Request::json()
        );

        Response::created(
            $servicio,
            'Servicio creado exitosamente'
        );
    }

    public function update($id): void
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->actualizar(
            $id,
            Request::json()
        );

        Response::success(
            [],
            200,
            'Servicio actualizado exitosamente'
        );
    }

    public function delete($id): void
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->eliminar($id);

        Response::success(
            [],
            200,
            'Servicio eliminado exitosamente'
        );
    }

    public function restore($id): void
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