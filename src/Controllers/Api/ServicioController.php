<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\ServicioService;

class ServicioController
{
    public function __construct(
        private readonly ServicioService $service,
        private readonly Authenticator $auth
    ) {
    }

    // GET /api/servicios
    public function index(Request $request): Response
    {
        $this->auth->verificarSiPidePapelera($request);

        return Response::success(
            $this->service->listar($this->filtrosPapelera($request))
        );
    }

    // GET /api/servicios/{id}
    public function show(Request $request, $id): Response
    {
        return Response::success(
            $this->service->obtener($id)
        );
    }

    // POST /api/servicios
    public function store(Request $request): Response
    {
        $this->auth->soloAdmin($request);

        $servicio = $this->service->crear($request->json());

        return Response::created(
            $servicio,
            'Servicio creado exitosamente'
        );
    }

    // PUT /api/servicios/{id}
    public function update(Request $request, $id): Response
    {
        $this->auth->soloAdmin($request);

        $this->service->actualizar($id, $request->json());

        return Response::success(
            [],
            200,
            'Servicio actualizado exitosamente'
        );
    }

    // DELETE /api/servicios/{id}
    public function delete(Request $request, $id): Response
    {
        $this->auth->soloAdmin($request);

        $this->service->eliminar($id);

        return Response::success(
            [],
            200,
            'Servicio eliminado exitosamente'
        );
    }

    // POST /api/servicios/{id}/restaurar
    public function restore(Request $request, $id): Response
    {
        $this->auth->soloAdmin($request);

        $this->service->restaurar($id);

        return Response::success(
            [],
            200,
            'Servicio restaurado exitosamente'
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
