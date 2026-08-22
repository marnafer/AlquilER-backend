<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Repositories\UsuarioRepository;
use App\Services\UsuarioService;

class UsuarioController
{
    private UsuarioService $service;

    public function __construct()
    {
        $this->service = new UsuarioService(
            new UsuarioRepository()
        );
    }

    /**
     * GET /api/usuarios
     */
    public function index(): void
    {
        $user = AutenticadorMiddleware::verificar();

        if ((int) $user->rol_id !== 2) {
            throw new ForbiddenException('Solo administradores');
        }

        Response::success(
            $this->service->listar()
        );
    }

    /**
     * GET /api/usuarios/{id}
     */
    public function show($id): void
    {
        $user = AutenticadorMiddleware::verificar();
        $id = (int) $id;

        if (
            (int) $user->rol_id !== 2
            && (int) $user->sub !== $id
        ) {
            throw new ForbiddenException('No autorizado');
        }

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
        $user = AutenticadorMiddleware::verificar();
        $id = (int) $id;

        if (
            (int) $user->rol_id !== 2
            && (int) $user->sub !== $id
        ) {
            throw new ForbiddenException('No autorizado');
        }

        $rawData = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($rawData)) {
            throw new BadRequestException('Datos inválidos');
        }

        $this->service->actualizar($id, $rawData);

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
        $user = AutenticadorMiddleware::verificar();
        $id = (int) $id;

        if (
            (int) $user->rol_id !== 2
            && (int) $user->sub !== $id
        ) {
            throw new ForbiddenException('No autorizado');
        }

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
        $user = AutenticadorMiddleware::verificar();

        if ((int) $user->rol_id !== 2) {
            throw new ForbiddenException('Solo administradores');
        }

        $this->service->restaurar((int) $id);

        Response::success(
            [],
            200,
            'Usuario restaurado correctamente'
        );
    }
}