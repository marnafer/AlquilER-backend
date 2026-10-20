<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\ProvinciaService;

class ProvinciaController
{
    public function __construct(
        private readonly ProvinciaService $service,
        private readonly Authenticator $auth
    ) {
    }

    // GET /api/provincias
    public function index(Request $request): Response
    {
        $this->auth->verificarSiPidePapelera($request);

        return Response::success(
            $this->service->listar($this->filtrosPapelera($request))
        );
    }

    // GET /api/provincias/{id}
    public function show(Request $request, $id): Response
    {
        return Response::success(
            $this->service->obtener($id)
        );
    }

    // POST /api/provincias
    public function store(Request $request): Response
    {
        $this->auth->soloAdmin($request);

        $provincia = $this->service->crear($request->json());

        return Response::created(
            $provincia,
            'Provincia creada exitosamente'
        );
    }

    // PUT /api/provincias/{id}
    public function update(Request $request, $id): Response
    {
        $this->auth->soloAdmin($request);

        $this->service->actualizar($id, $request->json());

        return Response::success(
            [],
            200,
            'Provincia actualizada exitosamente'
        );
    }

    // DELETE /api/provincias/{id}
    public function delete(Request $request, $id): Response
    {
        $this->auth->soloAdmin($request);

        $this->service->eliminar($id);

        return Response::success(
            [],
            200,
            'Provincia eliminada exitosamente'
        );
    }

    // POST /api/provincias/{id}/restaurar
    public function restore(Request $request, $id): Response
    {
        $this->auth->soloAdmin($request);

        $this->service->restaurar($id);

        return Response::success(
            [],
            200,
            'Provincia restaurada exitosamente'
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
