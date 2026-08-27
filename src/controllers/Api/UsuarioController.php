<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\UsuarioService;

class UsuarioController
{
    private readonly UsuarioService $service;

    public function __construct(UsuarioService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/usuarios
     */
    public function index(): void
    {
        AutenticadorMiddleware::soloAdmin();

        Response::success(
            $this->service->listar()
        );
    }

    /**
     * GET /api/usuarios/{id}
     */
    public function show($id): void
    {
        AutenticadorMiddleware::verificarPropietarioOAdmin($id);

        Response::success(
            $this->service->obtener($id)
        );
    }

    /**
     * GET /api/usuarios/me
     */
    public function profile(): void
    {
        $user = AutenticadorMiddleware::verificar();

        Response::success(
            $this->service->obtener((int) $user->sub)
        );
    }

    /**
     * PUT /api/usuarios/{id}
     */
    public function update($id): void
    {
        AutenticadorMiddleware::verificarPropietarioOAdmin($id);

        $this->service->actualizar(
            $id,
            Request::json()
        );

        Response::success(
            [],
            200,
            'Usuario actualizado correctamente'
        );
    }

    /**
     * DELETE /api/usuarios/{id}
     */
    public function delete($id): void
    {
        AutenticadorMiddleware::verificarPropietarioOAdmin($id);

        $this->service->eliminar($id);

        Response::success(
            [],
            200,
            'Usuario eliminado'
        );
    }

    /**
     * POST /api/usuarios/{id}/restaurar
     */
    public function restore($id): void
    {
        AutenticadorMiddleware::soloAdmin();

        $this->service->restaurar($id);

        Response::success(
            [],
            200,
            'Usuario restaurado correctamente'
        );
    }
}