<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\RolService;

class RolController
{
    public function __construct(
        private readonly RolService $service,
        private readonly Authenticator $auth
    ) {
    }

    // GET /api/roles
    public function index(Request $request): Response
    {
        $this->auth->verificarSiPidePapelera($request);

        return Response::success(
            $this->service->listar($this->filtrosPapelera($request))
        );
    }

    // GET /api/roles/{id}
    public function show(Request $request, $id): Response
    {
        return Response::success(
            $this->service->obtener($id)
        );
    }

    // POST /api/roles
    public function store(Request $request): Response
    {
        $this->auth->soloAdmin($request);

        $rol = $this->service->crear($request->json());

        return Response::created(
            $rol,
            'Rol creado exitosamente'
        );
    }

    // PUT /api/roles/{id}
    public function update(Request $request, $id): Response
    {
        $this->auth->soloAdmin($request);

        $this->service->actualizar($id, $request->json());

        return Response::success(
            [],
            200,
            'Rol actualizado exitosamente'
        );
    }

    // DELETE /api/roles/{id}
    public function delete(Request $request, $id): Response
    {
        $this->auth->soloAdmin($request);

        $this->service->eliminar($id);

        return Response::success(
            [],
            200,
            'Rol eliminado exitosamente'
        );
    }

    // POST /api/roles/{id}/restaurar
    public function restore(Request $request, $id): Response
    {
        $this->auth->soloAdmin($request);

        $this->service->restaurar($id);

        return Response::success(
            [],
            200,
            'Rol restaurado exitosamente'
        );
    }

    private function filtrosPapelera(Request $request): array
    {
        $filtros = [];

        if (($valor = $request->query('solo_eliminados')) !== null) {
            $filtros['solo_eliminados'] = filter_var(
                $valor,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if (($valor = $request->query('incluir_eliminados')) !== null) {
            $filtros['incluir_eliminados'] = filter_var(
                $valor,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        return $filtros;
    }
}
