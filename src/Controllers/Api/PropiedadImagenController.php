<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\PropiedadImagenService;

class PropiedadImagenController
{
    public function __construct(
        private readonly PropiedadImagenService $service,
        private readonly Authenticator $auth
    ) {
    }

    /**
     * GET /api/propiedad-imagenes
     */
    public function index(Request $request): Response
    {
        return Response::success(
            $this->service->listar()
        );
    }

    /**
     * GET /api/propiedad-imagenes/{id}
     */
    public function show(Request $request, $id): Response
    {
        return Response::success(
            $this->service->obtener($id)
        );
    }

    /**
     * POST /api/propiedad-imagenes
     */
    public function store(Request $request): Response
    {
        $user = $this->auth->verificar($request);

        $imagen = $this->service->crear(
            $request->postParams(),
            $request->file('imagen'),
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::created(
            $imagen,
            'Imagen guardada correctamente'
        );
    }

    /**
     * PUT /api/propiedad-imagenes/{id}/principal
     */
    public function setPrincipal(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $imagen = $this->service->establecerPrincipal(
            $id,
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::success(
            $imagen,
            200,
            'Imagen principal actualizada'
        );
    }

    /**
     * DELETE /api/propiedad-imagenes/{id}
     */
    public function delete(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $this->service->eliminar(
            $id,
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::success(
            [],
            200,
            'Imagen eliminada correctamente'
        );
    }
}
