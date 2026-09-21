<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\FavoritoService;

class FavoritoController
{
    private readonly FavoritoService $service;

    public function __construct(FavoritoService $service)
    {
        $this->service = $service;
    }

    /**
     * Obtener los favoritos del usuario autenticado.
     */
    public function index(): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;
        $rolId = (int) $user->rol_id;

        $favoritos = $this->service->listar(
            $usuarioId,
            $usuarioId,
            $rolId
        );

        Response::success(
            $favoritos,
            200,
            'Favoritos obtenidos correctamente'
        );
    }

    /**
     * Agregar una propiedad a favoritos.
     */
    public function store(): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;

        $data = Request::json();

        $this->service->agregar(
            $data,
            $usuarioId
        );

        Response::created(
            [
                'propiedad_id' => $data['propiedad_id'] ?? null,
                'es_favorito' => true
            ],
            'Propiedad agregada a favoritos'
        );
    }

    /**
     * Obtener los favoritos de un usuario específico.
     */
    public function indexByUsuario($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;
        $rolId = (int) $user->rol_id;

        $favoritos = $this->service->listar(
            $usuarioId,
            $id,
            $rolId
        );

        Response::success(
            $favoritos,
            200,
            'Favoritos del usuario obtenidos correctamente'
        );
    }

    /**
     * Eliminar una propiedad de favoritos.
     */
    public function deleteByPropiedad($propiedadId): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;

        $this->service->eliminar(
            $propiedadId,
            $usuarioId
        );

        Response::success(
            null,
            200,
            'Propiedad eliminada de favoritos'
        );
    }
}