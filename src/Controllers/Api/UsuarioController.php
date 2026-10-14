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
        $user = AutenticadorMiddleware::verificar();

        Response::success(
            $this->service->obtenerAutorizado(
                $id,
                (int) $user->sub,
                (int) $user->rol_id
            )
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
     * GET /api/usuarios/{id}/perfil-publico
     */
    public function publicProfile($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        Response::success(
            $this->service->obtenerPerfilPublico(
                $id,
                (int) $user->sub,
                (int) $user->rol_id
            )
        );
    }

    /**
     * POST /api/admin/usuarios
     */
    public function storeAdmin(): void
    {
        $admin = AutenticadorMiddleware::soloAdmin();

        $usuario = $this->service->crearDesdeAdmin(
            Request::json(),
            (int) $admin->sub
        );

        Response::created(
            $usuario,
            'Usuario creado correctamente'
        );
    }

    /**
     * PUT /api/usuarios/{id}
     */
    public function update($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $this->service->actualizar(
            $id,
            Request::json(),
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::success([], 200, 'Usuario actualizado correctamente');
    }

    /**
     * DELETE /api/usuarios/{id}
     */
    public function delete($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $this->service->eliminar(
            $id,
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::success([], 200, 'Usuario eliminado');
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