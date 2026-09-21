<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\PropiedadService;

class PropiedadController
{
    public function __construct(
        private readonly PropiedadService $service
    ) {
    }

    // GET /api/propiedades
    public function index(): void
    {
        Response::success(
            $this->service->listar()
        );
    }

    // GET /api/propiedades/mis-propiedades
    public function misPropiedades(): void
    {
        $user = AutenticadorMiddleware::verificar();

        Response::success(
            $this->service->misPropiedades(
                (int) $user->sub
            )
        );
    }

    // GET /api/propiedades/{id}
    public function show($id): void
    {
        Response::success(
            $this->service->obtener($id)
        );
    }

    // POST /api/propiedades
    public function store(): void
    {
        $user = AutenticadorMiddleware::verificar();

        $propiedad = $this->service->crear(
            Request::json(),
            (int) $user->sub
        );

        Response::created(
            $propiedad,
            'Propiedad creada exitosamente'
        );
    }

    // PUT /api/propiedades/{id}
    public function update($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $this->service->actualizar(
            (int) $user->sub,
            (int) $user->rol_id,
            $id,
            Request::json()
        );

        Response::success(
            [],
            200,
            'Propiedad actualizada exitosamente'
        );
    }

    // DELETE /api/propiedades/{id}
    public function delete($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $this->service->eliminar(
            (int) $user->sub,
            (int) $user->rol_id,
            $id
        );

        Response::success(
            [],
            200,
            'Propiedad eliminada exitosamente'
        );
    }

    // PATCH /api/propiedades/{id}/restaurar
    public function restore($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $this->service->restaurar(
            (int) $user->sub,
            (int) $user->rol_id,
            $id
        );

        Response::success(
            [],
            200,
            'Propiedad restaurada exitosamente'
        );
    }
}