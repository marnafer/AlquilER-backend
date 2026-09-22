<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
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
            $this->service->obtenerConRol((int) $user->sub)
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