<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Services\FavoritoService;
use App\Middlewares\AutenticadorMiddleware;

class FavoritoController
{
    private readonly FavoritoService $service;

    public function __construct(FavoritoService $service)
    {
        $this->service = $service;
    }

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

    public function store(): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;

        $data = Request::json();

        if (
            !isset($data['propiedad_id'])
            || !is_numeric($data['propiedad_id'])
        ) {
            Response::badRequest(
                'El campo propiedad_id es requerido y debe ser numérico'
            );

            return;
        }

        $propiedadId = (int) $data['propiedad_id'];

        $this->service->agregar(
            $usuarioId,
            $propiedadId
        );

        Response::created(
            [
                'propiedad_id' => $propiedadId,
                'es_favorito' => true
            ],
            'Propiedad agregada a favoritos'
        );
    }

    public function indexByUsuario($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;
        $rolId = (int) $user->rol_id;
        $usuarioConsultadoId = (int) $id;

        if ($usuarioConsultadoId <= 0) {
            Response::badRequest(
                'ID de usuario inválido'
            );

            return;
        }

        $favoritos = $this->service->listar(
            $usuarioId,
            $usuarioConsultadoId,
            $rolId
        );

        Response::success(
            $favoritos,
            200,
            'Favoritos del usuario obtenidos correctamente'
        );
    }

    public function deleteByPropiedad($propiedadId): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;

        if (
            !is_numeric($propiedadId)
            || (int) $propiedadId <= 0
        ) {
            Response::badRequest(
                'ID de propiedad inválido'
            );

            return;
        }

        $this->service->eliminar(
            $usuarioId,
            (int) $propiedadId
        );

        Response::success(
            null,
            200,
            'Propiedad eliminada de favoritos'
        );
    }
}