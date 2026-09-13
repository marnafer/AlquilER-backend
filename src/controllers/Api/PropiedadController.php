<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\PropiedadService;


class PropiedadController
{

    private readonly PropiedadService $service;

    public function __construct(PropiedadService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/propiedades
     */
    public function index()
    {
         Response::success(
            $this->service->listar()
        );
    }

    /**
     * GET /api/propiedades/{id}
     */
    public function show($id)
    {
        Response::success(
                $this->service->obtener($id)
        );
    }

    /**
     * POST /api/propiedades
     */
    public function store()
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

    /**
     * PUT /api/propiedades/{id}
     */
    public function update($id)
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

    /**
     * DELETE /api/propiedades/{id}
     */
    public function delete($id)
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

    /**
     * PATCH /api/propiedades/{id}/restaurar
     */
    public function restore($id)
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

    /**
     * Métodos alias en español para compatibilidad con tests
     */
    public function listar()
    {
        return $this->index();
    }

    public function crear()
    {
        return $this->store();
    }

    public function obtener($id)
    {
        return $this->show($id);
    }

    public function actualizar($request, $id)
    {
        return $this->update($id);
    }

    public function eliminar($request, $id)
    {
        return $this->delete($id);
    }

    public function restaurar($request, $id)
    {
        return $this->restore($id);
    }
}