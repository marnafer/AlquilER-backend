<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\FavoritoService;

class FavoritoController
{
    public function __construct(
        private readonly FavoritoService $service,
        private readonly Authenticator $auth
    ) {
    }

    /**
     * Obtener los favoritos del usuario autenticado.
     */
    public function index(Request $request): Response
    {
        $user = $this->auth->verificar($request);

        $usuarioId = (int) $user->sub;
        $rolId = (int) $user->rol_id;

        $favoritos = $this->service->listar(
            $usuarioId,
            $usuarioId,
            $rolId
        );

        return Response::success(
            $favoritos,
            200,
            'Favoritos obtenidos correctamente'
        );
    }

    /**
     * Agregar una propiedad a favoritos.
     */
    public function store(Request $request): Response
    {
        $user = $this->auth->verificar($request);

        $usuarioId = (int) $user->sub;

        $data = $request->json();

        $this->service->agregar(
            $data,
            $usuarioId
        );

        return Response::created(
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
    public function indexByUsuario(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $usuarioId = (int) $user->sub;
        $rolId = (int) $user->rol_id;

        $favoritos = $this->service->listar(
            $usuarioId,
            $id,
            $rolId
        );

        return Response::success(
            $favoritos,
            200,
            'Favoritos del usuario obtenidos correctamente'
        );
    }

    /**
     * Eliminar una propiedad de favoritos.
     */
    public function deleteByPropiedad(Request $request, $propiedadId): Response
    {
        $user = $this->auth->verificar($request);

        $usuarioId = (int) $user->sub;

        $this->service->eliminar(
            $propiedadId,
            $usuarioId
        );

        return Response::success(
            null,
            200,
            'Propiedad eliminada de favoritos'
        );
    }
}
