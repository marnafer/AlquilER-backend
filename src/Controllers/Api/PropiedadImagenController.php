<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\PropiedadImagenService;

class PropiedadImagenController
{
    private readonly PropiedadImagenService $service;

    public function __construct(PropiedadImagenService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/propiedad-imagenes
     */
    public function index(): void
    {
        Response::success(
            $this->service->listar()
        );
    }

    /**
     * GET /api/propiedad-imagenes/{id}
     */
    public function show($id): void
    {
        Response::success(
            $this->service->obtener($id)
        );
    }

    /**
     * POST /api/propiedad-imagenes
     */
    public function store(): void
    {
        $user = AutenticadorMiddleware::verificar();

        $imagen = $this->service->crear(
            $_POST,
            $_FILES['imagen'] ?? null,
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::created(
            $imagen,
            'Imagen guardada correctamente'
        );
    }

    /**
     * PUT /api/propiedad-imagenes/{id}/principal
     */
    public function setPrincipal($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $imagen = $this->service->establecerPrincipal(
            $id,
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::success(
            $imagen,
            200,
            'Imagen principal actualizada'
        );
    }

    /**
     * DELETE /api/propiedad-imagenes/{id}
     */
    public function delete($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $this->service->eliminar(
            $id,
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::success(
            [],
            200,
            'Imagen eliminada correctamente'
        );
    }
}