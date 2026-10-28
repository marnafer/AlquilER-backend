<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\PropiedadService;

class PropiedadController
{
    public function __construct(
        private readonly PropiedadService $service,
        private readonly Authenticator $auth
    ) {
    }

    // GET /api/propiedades
    public function index(Request $request): Response
    {
        return Response::success(
            $this->service->listar($request->queryParams())
        );
    }

    // GET /api/propiedades/destacadas
    public function destacadas(Request $request): Response
    {
        return Response::success(
            $this->service->listarDestacadas()
        );
    }

    // GET /api/admin/propiedades
    public function adminIndex(Request $request): Response
    {
        $this->auth->soloAdmin($request);

        return Response::success(
            $this->service->listarParaAdmin($request->queryParams())
        );
    }

    // GET /api/propiedades/mis-propiedades
    public function misPropiedades(Request $request): Response
    {
        $user = $this->auth->verificar($request);

        return Response::success(
            $this->service->misPropiedades((int) $user->sub)
        );
    }

    // GET /api/propiedades/{id}
    public function show(Request $request, $id): Response
    {
        return Response::success(
            $this->service->obtener($id)
        );
    }

    // POST /api/propiedades
    public function store(Request $request): Response
    {
        $user = $this->auth->verificar($request);

        $propiedad = $this->service->crear(
            $request->json(),
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::created(
            $propiedad,
            'Propiedad creada exitosamente'
        );
    }

    // PUT /api/propiedades/{id}
    public function update(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $this->service->actualizar(
            (int) $user->sub,
            (int) $user->rol_id,
            $id,
            $request->json()
        );

        return Response::success(
            [],
            200,
            'Propiedad actualizada exitosamente'
        );
    }

    // DELETE /api/propiedades/{id}
    public function delete(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $this->service->eliminar(
            (int) $user->sub,
            (int) $user->rol_id,
            $id
        );

        return Response::success(
            [],
            200,
            'Propiedad eliminada exitosamente'
        );
    }

    // POST /api/propiedades/{id}/restaurar
    public function restore(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $this->service->restaurar(
            (int) $user->sub,
            (int) $user->rol_id,
            $id
        );

        return Response::success(
            [],
            200,
            'Propiedad restaurada exitosamente'
        );
    }
}
