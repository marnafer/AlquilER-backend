<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\ReservaService;

class ReservaController
{
    public function __construct(
        private readonly ReservaService $service,
        private readonly Authenticator $auth
    ) {
    }

    // GET /api/reservas
    public function index(Request $request): Response
    {
        $usuario = $this->auth->verificar($request);

        return Response::success(
            $this->service->listar(
                (int) $usuario->sub,
                (int) $usuario->rol_id,
                $this->filtros($request)
            )
        );
    }

    // GET /api/reservas/{id}
    public function show(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        return Response::success(
            $this->service->obtener(
                $id,
                (int) $usuario->sub,
                (int) $usuario->rol_id
            )
        );
    }

    // POST /api/reservas
    public function store(Request $request): Response
    {
        $usuario = $this->auth->verificar($request);

        $id = $this->service->crear(
            $request->json(),
            (int) $usuario->sub
        );

        return Response::created(
            ['id' => $id],
            'Reserva creada correctamente'
        );
    }

    // POST /api/admin/reservas
    public function storeAdmin(Request $request): Response
    {
        $admin = $this->auth->soloAdmin($request);

        $id = $this->service->crearDesdeAdmin(
            $request->json(),
            (int) $admin->sub
        );

        return Response::created(
            ['id' => $id],
            'Reserva creada correctamente'
        );
    }

    // PUT /api/reservas/{id}/confirmar
    public function confirm(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->confirmar(
            $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        return Response::success(
            [],
            200,
            'Reserva confirmada correctamente'
        );
    }

    // PUT /api/reservas/{id}/rechazar
    public function reject(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->rechazar(
            $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        return Response::success(
            [],
            200,
            'Reserva rechazada correctamente'
        );
    }

    // PUT /api/reservas/{id}/finalizar
    public function finalize(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->finalizar(
            $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        return Response::success(
            [],
            200,
            'Reserva finalizada correctamente'
        );
    }

    // PUT /api/reservas/{id}/cancelar
    public function cancel(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->cancelar(
            $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        return Response::success(
            [],
            200,
            'Reserva cancelada correctamente'
        );
    }

    // PUT /api/reservas/{id}
    public function update(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->actualizar(
            $id,
            $request->json(),
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        return Response::success(
            [],
            200,
            'Reserva actualizada correctamente'
        );
    }

    // DELETE /api/reservas/{id}
    public function delete(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->eliminar(
            $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        return Response::success(
            [],
            200,
            'Reserva eliminada correctamente'
        );
    }

    // POST /api/reservas/{id}/restore
    public function restore(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->restaurar(
            $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        return Response::success(
            [],
            200,
            'Reserva restaurada correctamente'
        );
    }

    /**
     * Reconstruye los filtros de listado desde la query string.
     *
     * @return array<string, mixed>
     */
    private function filtros(Request $request): array
    {
        $filtros = [];

        foreach (['estado', 'usuario_id', 'propiedad_id'] as $campo) {
            if (($valor = $request->query($campo)) !== null) {
                $filtros[$campo] = $campo === 'estado'
                    ? $valor
                    : (int) $valor;
            }
        }

        foreach (['solo_eliminados', 'incluir_eliminados'] as $campo) {
            if (($valor = $request->query($campo)) !== null) {
                $filtros[$campo] = filter_var(
                    $valor,
                    FILTER_VALIDATE_BOOLEAN
                );
            }
        }

        return $filtros;
    }
}
