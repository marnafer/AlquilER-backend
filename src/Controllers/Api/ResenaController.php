<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\ResenaService;

class ResenaController
{
    public function __construct(
        private readonly ResenaService $service,
        private readonly Authenticator $auth
    ) {
    }

    // GET /api/resenas
    public function index(Request $request): Response
    {
        $this->auth->verificarSiPidePapelera($request);

        return Response::success(
            $this->service->listar($this->filtros($request)),
            200,
            'Reseñas obtenidas correctamente'
        );
    }

    // GET /api/resenas/{id}
    public function show(Request $request, $id): Response
    {
        return Response::success(
            $this->service->obtener($id),
            200,
            'Reseña encontrada'
        );
    }

    // GET /api/resenas/reserva/{reservaId}
    public function getByReserva(Request $request, $reservaId): Response
    {
        return Response::success(
            $this->service->obtenerPorReserva($reservaId),
            200,
            'Reseñas de la reserva obtenidas'
        );
    }

    // GET /api/resenas/propiedad/{propiedadId}
    public function getByPropiedad(Request $request, $propiedadId): Response
    {
        $resenas = $this->service->obtenerPorPropiedad($propiedadId);

        $promedio = $this->service->promedioPropiedad($propiedadId);

        return Response::success([
            'items' => $resenas,
            'total' => $resenas->count(),
            'promedio' => $promedio,
        ], 200, 'Reseñas de la propiedad obtenidas');
    }

    // GET /api/resenas/usuario/{usuarioId}
    public function getByUsuario(Request $request, $usuarioId): Response
    {
        $resenas = $this->service->obtenerPorUsuario($usuarioId);

        $promedio = $this->service->promedioUsuario($usuarioId);

        return Response::success([
            'items' => $resenas,
            'total' => $resenas->count(),
            'promedio' => $promedio,
        ], 200, 'Reseñas del usuario obtenidas');
    }

    // GET /api/resenas/calificador/{calificadorId}
    public function getByCalificador(Request $request, $calificadorId): Response
    {
        return Response::success(
            $this->service->obtenerPorCalificador($calificadorId),
            200,
            'Reseñas del calificador obtenidas'
        );
    }

    // GET /api/resenas/mis-propiedades
    public function getMyPropertiesReviews(Request $request): Response
    {
        $usuario = $this->auth->verificar($request);

        $resenas = $this->service->obtenerResenasDeMisPropiedades(
            (int) $usuario->sub
        );

        return Response::success([
            'items' => $resenas,
            'total' => $resenas->count(),
        ], 200, 'Reseñas de tus propiedades obtenidas correctamente');
    }

    // GET /api/resenas/como-inquilino
    public function getMyTenantReviews(Request $request): Response
    {
        $usuario = $this->auth->verificar($request);

        $resenas = $this->service->obtenerMisResenasComoInquilino(
            (int) $usuario->sub
        );

        return Response::success([
            'items' => $resenas,
            'total' => $resenas->count(),
        ], 200, 'Reseñas realizadas como inquilino obtenidas correctamente');
    }

    // POST /api/admin/resenas
    public function storeAdmin(Request $request): Response
    {
        $admin = $this->auth->soloAdmin($request);

        $resena = $this->service->crearDesdeAdmin(
            $request->json(),
            (int) $admin->sub
        );

        return Response::created(
            $resena,
            'Reseña creada correctamente'
        );
    }

    // POST /api/resenas
    public function store(Request $request): Response
    {
        $usuario = $this->auth->verificar($request);

        $resena = $this->service->crear(
            $request->json(),
            (int) $usuario->sub
        );

        return Response::created(
            $resena,
            'Reseña creada exitosamente'
        );
    }

    // PUT /api/resenas/{id}
    public function update(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->actualizar(
            (int) $id,
            $request->json(),
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        return Response::success(
            null,
            200,
            'Reseña actualizada correctamente'
        );
    }

    // DELETE /api/resenas/{id}
    public function delete(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->eliminar(
            (int) $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        return Response::success(
            [],
            200,
            'Reseña eliminada exitosamente'
        );
    }

    // POST /api/resenas/{id}/restaurar
    public function restore(Request $request, $id): Response
    {
        $usuario = $this->auth->verificar($request);

        $this->service->restaurar(
            (int) $id,
            (int) $usuario->sub,
            (int) $usuario->rol_id
        );

        return Response::success(
            null,
            200,
            'Reseña restaurada exitosamente'
        );
    }

    /**
     * Reconstruye los filtros de listado desde la query string.
     *
     * Se conserva el casting original campo por campo: el servicio espera
     * enteros para los rangos de calificación e identificadores.
     *
     * @return array<string, mixed>
     */
    private function filtros(Request $request): array
    {
        $filtros = [];

        $enteros = [
            'calificacion',
            'calificacion_min',
            'calificacion_max',
            'calificador_id',
            'reserva_id',
            'propiedad_id',
            'usuario_id',
        ];

        $textos = [
            'tipo',
            'fecha_desde',
            'fecha_hasta',
        ];

        foreach ($textos as $campo) {
            if (($valor = $request->query($campo)) !== null) {
                $filtros[$campo] = $valor;
            }
        }

        foreach ($enteros as $campo) {
            if (($valor = $request->query($campo)) !== null) {
                $filtros[$campo] = (int) $valor;
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
