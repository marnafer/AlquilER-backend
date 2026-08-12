<?php

namespace App\Controllers\Api;

use App\Models\Reserva;
use App\Models\Propiedad;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Sanitizers\ReservaSanitizer;
use App\Validators\ReservaValidator;

class ReservaController
{
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

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
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
                Response::validationError(
                    $validacion['errors']
                );
            }

            $reserva = Reserva::find($id);

            if (!$reserva) {
                Response::notFound('Reserva no encontrada');
            }

            $propiedad = Propiedad::find(
                $reserva->propiedad_id
            );

            $esAdmin = $user->rol_id == 3;
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
                Response::forbidden();
            }

            Response::success($reserva);

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
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

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
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
                Response::notFound(
                    'Propiedad no encontrada'
                );
            }

            if (
                $user->rol_id != 3 &&
                $propiedad->usuario_id != $user->sub
            ) {
                Response::forbidden();
            }

            $reservas = Reserva::where(
                'propiedad_id',
                $propiedadId
            )->get();

            Response::success([
                'reservas' => $reservas,
                'total' => $reservas->count()
            ]);

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
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
            Response::badRequest(
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
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $propiedad = Propiedad::find(
                $san['propiedad_id']
            );

            if (!$propiedad) {
                Response::notFound(
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

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
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
                Response::notFound(
                    'Reserva no encontrada'
                );
            }

            $propiedad = Propiedad::find(
                $reserva->propiedad_id
            );

            if (
                $user->rol_id != 3 &&
                $propiedad->usuario_id != $user->sub
            ) {
                Response::forbidden();
            }

            if (
                $reserva->estado !== 'pendiente'
            ) {
                Response::badRequest(
                    'La reserva no puede aprobarse'
                );
            }

            $reserva->estado = 'aprobada';
            $reserva->save();

            Response::success(
                $reserva,
                200,
                'Reserva aprobada'
            );

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
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
                Response::notFound(
                    'Reserva no encontrada'
                );
            }

            $propiedad = Propiedad::find(
                $reserva->propiedad_id
            );

            if (
                $user->rol_id != 3 &&
                $propiedad->usuario_id != $user->sub
            ) {
                Response::forbidden();
            }

            if (
                $reserva->estado !== 'pendiente'
            ) {
                Response::badRequest(
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

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
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
                Response::notFound(
                    'Reserva no encontrada'
                );
            }

            if (
                $user->rol_id != 3 &&
                $reserva->usuario_id != $user->sub
            ) {
                Response::forbidden();
            }

            if (
                !in_array(
                    $reserva->estado,
                    ['pendiente', 'aprobada']
                )
            ) {
                Response::badRequest(
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

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
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
                Response::notFound(
                    'Reserva no encontrada'
                );
            }

            $propiedad = Propiedad::find(
                $reserva->propiedad_id
            );

            if (
                $user->rol_id != 3 &&
                $propiedad->usuario_id != $user->sub
            ) {
                Response::forbidden();
            }

            if (
                $reserva->estado !== 'aprobada'
            ) {
                Response::badRequest(
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

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
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
                Response::notFound(
                    'Reserva no encontrada'
                );
            }

            $reserva->delete();

            Response::success(
                [],
                200,
                'Reserva eliminada'
            );

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }
}