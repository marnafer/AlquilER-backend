<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\PropiedadServicioService;

class PropiedadServicioController
{
    public function __construct(
        private readonly PropiedadServicioService $service
    ) {
    }

    // GET /api/propiedades/{propiedadId}/servicios
    public function index(
        $request,
        $propiedadId
    ): void {
        $servicios = $this->service->listar(
            $propiedadId
        );

        Response::success([
            'items' => $servicios,
            'total' => count($servicios),
        ]);
    }

    // GET /api/servicios/{servicioId}/propiedades
    public function getPropiedadesByServicio(
        $request,
        $servicioId
    ): void {
        $propiedades = $this->service->listarPorServicio(
            $servicioId
        );

        Response::success([
            'items' => $propiedades,
            'total' => count($propiedades),
        ]);
    }

    // POST /api/propiedades/{propiedadId}/servicios
    public function store(
        $request,
        $propiedadId
    ): void {
        $user = AutenticadorMiddleware::verificar();

        $data = Request::json();

       $resultado = $this->service->asignar(
            $propiedadId,
            $data['servicio_id'] ?? null,
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::created(
            $resultado,
            'Servicio asignado correctamente'
        );
    }

    // POST /api/propiedades/{propiedadId}/servicios/multiple
    public function storeMultiple(
        $request,
        $propiedadId
    ): void {
        $user = AutenticadorMiddleware::verificar();

        $data = Request::json();

        $resultados = $this->service->asignarMultiples(
            $propiedadId,
            $data['servicio_ids'] ?? [],
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::success(
            $resultados,
            200,
            'Servicios asignados correctamente'
        );
    }

    // PUT /api/propiedades/{propiedadId}/servicios
    public function update(
        $request,
        $propiedadId
    ): void {
        $user = AutenticadorMiddleware::verificar();

        $data = Request::json();

        $resultados = $this->service->sincronizar(
            $propiedadId,
            $data['servicio_ids'] ?? [],
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::success(
            $resultados,
            200,
            'Servicios sincronizados correctamente'
        );
    }

    // DELETE /api/propiedades/{propiedadId}/servicios/{servicioId}
    public function delete(
        $request,
        $propiedadId,
        $servicioId
    ): void {
        $user = AutenticadorMiddleware::verificar();

        $this->service->desasignar(
            $propiedadId,
            $servicioId,
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::success(
            [],
            200,
            'Servicio desasignado correctamente'
        );
    }
}