<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\PropiedadServicioService;

class PropiedadServicioController
{
    public function __construct(
        private readonly PropiedadServicioService $service,
        private readonly Authenticator $auth
    ) {
    }

    // GET /api/propiedades/{propiedadId}/servicios
    public function index(Request $request, $propiedadId): Response
    {
        $servicios = $this->service->listar($propiedadId);

        return Response::success([
            'items' => $servicios,
            'total' => count($servicios),
        ]);
    }

    // GET /api/servicios/{servicioId}/propiedades
    public function getPropiedadesByServicio(Request $request, $servicioId): Response
    {
        $propiedades = $this->service->listarPorServicio($servicioId);

        return Response::success([
            'items' => $propiedades,
            'total' => count($propiedades),
        ]);
    }

    // POST /api/propiedades/{propiedadId}/servicios
    public function store(Request $request, $propiedadId): Response
    {
        $user = $this->auth->verificar($request);

        $data = $request->json();

        $resultado = $this->service->asignar(
            $propiedadId,
            $data['servicio_id'] ?? null,
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::created(
            $resultado,
            'Servicio asignado correctamente'
        );
    }

    // POST /api/propiedades/{propiedadId}/servicios/multiple
    public function storeMultiple(Request $request, $propiedadId): Response
    {
        $user = $this->auth->verificar($request);

        $data = $request->json();

        $resultados = $this->service->asignarMultiples(
            $propiedadId,
            $data['servicio_ids'] ?? [],
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::success(
            $resultados,
            200,
            'Servicios asignados correctamente'
        );
    }

    // PUT /api/propiedades/{propiedadId}/servicios
    public function update(Request $request, $propiedadId): Response
    {
        $user = $this->auth->verificar($request);

        $data = $request->json();

        $resultados = $this->service->sincronizar(
            $propiedadId,
            $data['servicio_ids'] ?? [],
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::success(
            $resultados,
            200,
            'Servicios sincronizados correctamente'
        );
    }

    // DELETE /api/propiedades/{propiedadId}/servicios/{servicioId}
    public function delete(Request $request, $propiedadId, $servicioId): Response
    {
        $user = $this->auth->verificar($request);

        $this->service->desasignar(
            $propiedadId,
            $servicioId,
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::success(
            [],
            200,
            'Servicio desasignado correctamente'
        );
    }
}
