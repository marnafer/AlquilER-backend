<?php

namespace App\Controllers\Api;

use App\Models\Reserva;
use App\Models\Propiedad;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Sanitizers\ReservaSanitizer;
use App\Validators\ReservaValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\BadRequestException;
use App\Services\ReservaService;

class ReservaController
{
    private ?ReservaService $service = null;

    public function __construct(ReservaService $service = null)
    {
        $this->service = $service;
    }
    /**
     * GET /api/reservas
     * Solo admin
     */
    public function index()
    {
        AutenticadorMiddleware::soloAdmin();

        try {

            $reservas = Reserva::all();

            Response::success([
                'items' => $reservas,
                'total' => $reservas->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/reservas/{id}
     */
    public function show($id)
    {
        $user = AutenticadorMiddleware::verificar();

        try {

            $validacion = ReservaValidator::validarSoloId($id);

            if (!$validacion['success']) {
                throw new ValidationException(
                    $validacion['errors']
                );
            }

            $reserva = Reserva::find($id);

            if (!$reserva) {
                throw new NotFoundException('Reserva no encontrada');
            }

            $propiedad = Propiedad::find(
                $reserva->propiedad_id
            );

            $esAdmin = $user->rol_id == 2;
            $esPropietario =
                $propiedad &&
                $propiedad->usuario_id == $user->sub;

            $esUsuario =
                $reserva->usuario_id == $user->sub;

            if (
                !$esAdmin &&
                !$esPropietario &&
                !$esUsuario
            ) {
                throw new ForbiddenException();
            }

            Response::success($reserva);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/reservas/mis-reservas
     */
    public function misReservas()
    {
        $user = AutenticadorMiddleware::verificar();

        try {

            $reservas = Reserva::where(
                'usuario_id',
                $user->sub
            )->get();

            Response::success([
                'items' => $reservas,
                'total' => $reservas->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/reservas/propiedad/{id}
     */
    public function reservasPorPropiedad($propiedadId)
    {
        $user = AutenticadorMiddleware::verificar();

        try {

            $propiedad = Propiedad::find($propiedadId);

            if (!$propiedad) {
                throw new NotFoundException(
                    'Propiedad no encontrada'
                );
            }

            if (
                $user->rol_id != 2 &&
                $propiedad->usuario_id != $user->sub
            ) {
                throw new ForbiddenException();
            }

            $reservas = Reserva::where(
                'propiedad_id',
                $propiedadId
            )->get();

            Response::success([
                'reservas' => $reservas,
                'total' => $reservas->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * POST /api/reservas
     */
    public function store()
    {
        $user = AutenticadorMiddleware::verificar();

        $raw = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($raw)) {
            throw new BadRequestException(
                'JSON inválido'
            );
        }

        $san = ReservaSanitizer::sanitizar($raw);

        $san['usuario_id'] = $user->sub;

        $validacion =
            ReservaValidator::validarCrear(
                $san
            );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $propiedad = Propiedad::find(
                $san['propiedad_id']
            );

            if (!$propiedad) {
                throw new NotFoundException(
                    'Propiedad no encontrada'
                );
            }

            $reserva = Reserva::create([
                'propiedad_id' =>
                    $san['propiedad_id'],

                'usuario_id' =>
                    $user->sub,

                'fecha_inicio_alquiler' =>
                    $san['fecha_inicio_alquiler'],

                'fecha_fin_alquiler' =>
                    $san['fecha_fin_alquiler'],

                'estado' => 'pendiente'
            ]);

            Response::created(
                $reserva,
                'Reserva creada correctamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * PUT /api/reservas/{id}/aprobar
     */
    public function aprobar($id)
    {
        $user = AutenticadorMiddleware::verificar();

        try {

            $reserva = Reserva::find($id);

            if (!$reserva) {
                throw new NotFoundException(
                    'Reserva no encontrada'
                );
            }

            $propiedad = Propiedad::find(
                $reserva->propiedad_id
            );

            if (
                $user->rol_id != 2 &&
                $propiedad->usuario_id != $user->sub
            ) {
                throw new ForbiddenException();
            }

            if (
                $reserva->estado !== 'pendiente'
            ) {
                throw new BadRequestException(
                    'La reserva no puede aprobarse'
                );
            }

            $reserva->estado = 'confirmada';
            $reserva->save();

            Response::success(
                $reserva,
                200,
                'Reserva aprobada'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * PUT /api/reservas/{id}/rechazar
     */
    public function rechazar($id)
    {
        $user = AutenticadorMiddleware::verificar();

        try {

            $reserva = Reserva::find($id);

            if (!$reserva) {
                throw new NotFoundException(
                    'Reserva no encontrada'
                );
            }

            $propiedad = Propiedad::find(
                $reserva->propiedad_id
            );

            if (
                $user->rol_id != 2 &&
                $propiedad->usuario_id != $user->sub
            ) {
                throw new ForbiddenException();
            }

            if (
                $reserva->estado !== 'pendiente'
            ) {
                throw new BadRequestException(
                    'La reserva no puede rechazarse'
                );
            }

            $reserva->estado = 'rechazada';
            $reserva->save();

            Response::success(
                $reserva,
                200,
                'Reserva rechazada'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * PUT /api/reservas/{id}/cancelar
     */
    public function cancelar($id)
    {
        $user = AutenticadorMiddleware::verificar();

        try {

            $reserva = Reserva::find($id);

            if (!$reserva) {
                throw new NotFoundException(
                    'Reserva no encontrada'
                );
            }

            if (
                $user->rol_id != 2 &&
                $reserva->usuario_id != $user->sub
            ) {
                throw new ForbiddenException();
            }

            if (
                !in_array(
                    $reserva->estado,
                    ['pendiente', 'confirmada']
                )
            ) {
                throw new BadRequestException(
                    'La reserva no puede cancelarse'
                );
            }

            $reserva->estado = 'cancelada';
            $reserva->save();

            Response::success(
                $reserva,
                200,
                'Reserva cancelada'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * PUT /api/reservas/{id}/finalizar
     */
    public function finalizar($id)
    {
        $user = AutenticadorMiddleware::verificar();

        try {

            $reserva = Reserva::find($id);

            if (!$reserva) {
                throw new NotFoundException(
                    'Reserva no encontrada'
                );
            }

            $propiedad = Propiedad::find(
                $reserva->propiedad_id
            );

            if (
                $user->rol_id != 2 &&
                $propiedad->usuario_id != $user->sub
            ) {
                throw new ForbiddenException();
            }

            if (
                $reserva->estado !== 'confirmada'
            ) {
                throw new BadRequestException(
                    'La reserva no puede finalizarse'
                );
            }

            $reserva->estado = 'finalizada';
            $reserva->save();

            Response::success(
                $reserva,
                200,
                'Reserva finalizada'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * DELETE /api/reservas/{id}
     */
    public function delete($id)
    {
        AutenticadorMiddleware::soloAdmin();

        try {

            $reserva = Reserva::find($id);

            if (!$reserva) {
                throw new NotFoundException(
                    'Reserva no encontrada'
                );
            }

            $reserva->delete();

            Response::success(
                [],
                200,
                'Reserva eliminada'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * Métodos alias en español para compatibilidad con tests
     */
    public function listar($request = null)
    {
        if ($this->service) {
            try {
                $usuarioId = $request->usuario_id ?? null;
                if (!$usuarioId) {
                    Response::unauthorized('Usuario no autenticado');
                    return;
                }

                $reservas = $this->service->listar();
                Response::success($reservas, 200, 'Reservas obtenidas correctamente');
            } catch (\Exception $e) {
                $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        } else {
            return $this->index();
        }
    }

    public function obtener($request = null, $id = null)
    {
        return $this->show($id);
    }

    public function crear($request = null)
    {
        if ($this->service) {
            try {
                $usuarioId = $request->usuario_id ?? null;
                if (!$usuarioId) {
                    Response::unauthorized('Usuario no autenticado');
                    return;
                }

                // Parse JSON body
                $data = json_decode(file_get_contents('php://input'), true);
                $data['usuario_id'] = $usuarioId;

                $id = $this->service->crear($data);
                Response::created(['id' => $id], 'Reserva creada correctamente');
            } catch (\Exception $e) {
                $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
                if ($status === 400) {
                    Response::badRequest($e->getMessage());
                } else {
                    Response::json(['success' => false, 'error' => $e->getMessage()], $status);
                }
            }
        } else {
            return $this->store();
        }
    }

    public function actualizar($request = null, $id = null)
    {
        if ($this->service) {
            try {
                $usuarioId = $request->usuario_id ?? null;
                if (!$usuarioId) {
                    Response::unauthorized('Usuario no autenticado');
                    return;
                }

                // Parse JSON body
                $data = json_decode(file_get_contents('php://input'), true);

                $this->service->actualizar($id, $data);
                Response::success([], 200, 'Reserva actualizada correctamente');
            } catch (\Exception $e) {
                $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
                if ($status === 404) {
                    Response::notFound($e->getMessage());
                } elseif ($status === 403) {
                    Response::forbidden($e->getMessage());
                } else {
                    Response::json(['success' => false, 'error' => $e->getMessage()], $status);
                }
            }
        } else {
            // No hay método update en el controlador actual, usar aprobar como fallback
            Response::json(['success' => false, 'error' => 'Método no disponible'], 405);
        }
    }

    public function eliminar($request = null, $id = null)
    {
        if ($this->service) {
            try {
                $usuarioId = $request->usuario_id ?? null;
                if (!$usuarioId) {
                    Response::unauthorized('Usuario no autenticado');
                    return;
                }

                $this->service->eliminar($id, $usuarioId);
                Response::success([], 200, 'Reserva eliminada correctamente');
            } catch (\Exception $e) {
                $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
                if ($status === 404) {
                    Response::notFound($e->getMessage());
                } elseif ($status === 403) {
                    Response::forbidden($e->getMessage());
                } else {
                    Response::json(['success' => false, 'error' => $e->getMessage()], $status);
                }
            }
        } else {
            return $this->delete($id);
        }
    }

    public function restaurar($request = null, $id = null)
    {
        if ($this->service) {
            try {
                $usuarioId = $request->usuario_id ?? null;
                if (!$usuarioId) {
                    Response::unauthorized('Usuario no autenticado');
                    return;
                }

                $this->service->restaurar($id, $usuarioId);
                Response::success([], 200, 'Reserva restaurada correctamente');
            } catch (\Exception $e) {
                $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
                if ($status === 404) {
                    Response::notFound($e->getMessage());
                } else {
                    Response::json(['success' => false, 'error' => $e->getMessage()], $status);
                }
            }
        } else {
            // No hay método de restore en el controlador actual
            Response::json(['success' => false, 'error' => 'Método no disponible'], 405);
        }
    }

    public function listarPorUsuario($request = null, $usuarioId = null)
    {
        if ($this->service) {
            try {
                $user = $request->usuario_id ?? null;
                if (!$user) {
                    Response::unauthorized('Usuario no autenticado');
                    return;
                }

                $reservas = $this->service->listarPorUsuario($usuarioId);
                Response::success($reservas, 200, 'Reservas obtenidas correctamente');
            } catch (\Exception $e) {
                $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        } else {
            return $this->misReservas();
        }
    }

    public function listarPorPropiedad($request = null, $propiedadId = null)
    {
        if ($this->service) {
            try {
                $usuarioId = $request->usuario_id ?? null;
                if (!$usuarioId) {
                    Response::unauthorized('Usuario no autenticado');
                    return;
                }

                $reservas = $this->service->listarPorPropiedad($propiedadId);
                Response::success($reservas, 200, 'Reservas obtenidas correctamente');
            } catch (\Exception $e) {
                $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        } else {
            return $this->reservasPorPropiedad($propiedadId);
        }
    }

    public function cambiarEstado($request = null, $id = null)
    {
        if ($this->service) {
            try {
                $usuarioId = $request->usuario_id ?? null;
                if (!$usuarioId) {
                    Response::unauthorized('Usuario no autenticado');
                    return;
                }

                // Parse JSON body
                $data = json_decode(file_get_contents('php://input'), true);
                $estado = $data['estado'] ?? null;

                if (!$estado) {
                    Response::badRequest('El campo estado es requerido');
                    return;
                }

                $this->service->cambiarEstado($id, $estado, $usuarioId);
                Response::success([], 200, 'Estado de la reserva actualizado');
            } catch (\Exception $e) {
                $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
                if ($status === 404) {
                    Response::notFound($e->getMessage());
                } elseif ($status === 403) {
                    Response::forbidden($e->getMessage());
                } else {
                    Response::json(['success' => false, 'error' => $e->getMessage()], $status);
                }
            }
        } else {
            // Intentar usar los métodos aprobar/rechazar/cancelar/finalizar
            Response::json(['success' => false, 'error' => 'Método no disponible'], 405);
        }
    }

    public function verificarDisponibilidad($request = null)
    {
        if ($this->service) {
            try {
                // Parse JSON body
                $data = json_decode(file_get_contents('php://input'), true);
                $propiedadId = $data['propiedad_id'] ?? null;
                $fechaInicio = $data['fecha_inicio'] ?? null;
                $fechaFin = $data['fecha_fin'] ?? null;

                if (!$propiedadId || !$fechaInicio || !$fechaFin) {
                    Response::badRequest('Los campos propiedad_id, fecha_inicio y fecha_fin son requeridos');
                    return;
                }

                $disponible = $this->service->verificarDisponibilidad(
                    $propiedadId,
                    $fechaInicio,
                    $fechaFin
                );

                Response::success(
                    ['disponible' => $disponible],
                    200,
                    'Disponibilidad verificada'
                );
            } catch (\Exception $e) {
                $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
                Response::json(['success' => false, 'error' => $e->getMessage()], $status);
            }
        } else {
            Response::json(['success' => false, 'error' => 'Método no disponible'], 405);
        }
    }
}