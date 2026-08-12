<?php

namespace App\Controllers\Api;

use App\Models\Resena;
use App\Models\Reserva;
use App\Models\Propiedad;
use App\Models\Usuario;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Sanitizers\ResenaSanitizer;
use App\Validators\ResenaValidator;

class ResenaController
{
    /**
     * GET /api/resenas
     */
    public function index()
    {
        try {

            $resenas = Resena::getAll();

            Response::success([
                'items' => $resenas,
                'total' => count($resenas)
            ]);

        } catch (\Exception $e) {

            Response::serverError(
                $e->getMessage()
            );
        }
    }

    /**
     * GET /api/resenas/{id}
     */
    public function show($id)
    {
        $validacion =
            ResenaValidator::validarSoloId(
                $id
            );

        if (!$validacion['success']) {
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $resena =
                Resena::getById($id);

            if (!$resena) {
                Response::notFound(
                    'Reseña no encontrada'
                );
            }

            Response::success(
                $resena
            );

        } catch (\Exception $e) {

            Response::serverError(
                $e->getMessage()
            );
        }
    }

    /**
     * GET /api/resenas/propiedad/{id}
     */
    public function getByPropiedad($id)
{
    $idSan = ResenaSanitizer::sanitizarId($id);

    $validacion = ResenaValidator::validarSoloId($idSan);

    if (!$validacion['success']) {
        Response::validationError(
            $validacion['errors']
        );
    }

    try {

        $propiedad = Propiedad::find($idSan);

        if (!$propiedad) {
            Response::notFound(
                'Propiedad no encontrada'
            );
        }

        $resenas = Resena::getByPropiedad(
            $idSan
        );

        $promedio = Resena::getPromedioByPropiedad(
            $idSan
        );

        Response::success([
            'items' => $resenas,
            'promedio' => $promedio['promedio'],
            'total_resenas' => $promedio['total'],
            'propiedad_id' => $idSan
        ]);

    } catch (\Exception $e) {

        Response::serverError(
            $e->getMessage()
        );
    }
}

    /**
     * GET /api/resenas/usuario/{id}
     */
    public function getByUsuario($id)
{
    $idSan = ResenaSanitizer::sanitizarId($id);

    $validacion = ResenaValidator::validarSoloId($idSan);

    if (!$validacion['success']) {
        Response::validationError(
            $validacion['errors']
        );
    }

    try {

        $usuario = Usuario::find($idSan);

        if (!$usuario) {
            Response::notFound(
                'Usuario no encontrado'
            );
        }

        $resenas = Resena::getByUsuario(
            $idSan
        );

        Response::success([
            'items' => $resenas,
            'total' => count($resenas),
            'usuario_id' => $idSan
        ]);

    } catch (\Exception $e) {

        Response::serverError(
            $e->getMessage()
        );
    }
}

    /**
     * GET /api/resenas/estadisticas
     */
    public function getEstadisticas()
    {
        try {

            $estadisticas =
                Resena::getEstadisticas();

            Response::success(
                $estadisticas
            );

        } catch (\Exception $e) {

            Response::serverError(
                $e->getMessage()
            );
        }
    }

    /**
     * POST /api/resenas
     */
    public function store()
    {
        $user =
            AutenticadorMiddleware::verificar();

        $raw = json_decode(
            file_get_contents(
                'php://input'
            ),
            true
        );

        if (!is_array($raw)) {
            Response::badRequest(
                'JSON inválido'
            );
        }

        $san =
            ResenaSanitizer::sanitizarCrear(
                $raw
            );

        $validacion =
            ResenaValidator::validarCrear(
                $san
            );

        if (!$validacion['success']) {

            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $reserva =
                Reserva::find(
                    $san['reserva_id']
                );

            if (!$reserva) {

                Response::notFound(
                    'Reserva no encontrada'
                );
            }

            if (
                $reserva->usuario_id !=
                $user->sub
            ) {
                Response::forbidden(
                    'La reserva no pertenece al usuario autenticado'
                );
            }

            if (
                $reserva->estado !==
                'finalizada'
            ) {
                Response::badRequest(
                    'La reserva debe estar finalizada para poder reseñarla'
                );
            }

            if (
                Resena::existePorReserva(
                    $reserva->id
                )
            ) {
                Response::badRequest(
                    'Ya existe una reseña para esta reserva'
                );
            }

            $resena =
                Resena::createResena([
                    'reserva_id' =>
                        $san['reserva_id'],

                    'calificacion' =>
                        $san['calificacion'],

                    'comentario' =>
                        $san['comentario']
                ]);

            Response::created(
                $resena,
                'Reseña creada exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError(
                $e->getMessage()
            );
        }
    } 
    
    /**
     * PUT /api/resenas/{id}
     */
    public function update($id)
    {
        $user =
            AutenticadorMiddleware::verificar();

        $raw = json_decode(
            file_get_contents(
                'php://input'
            ),
            true
        );

        if (!is_array($raw)) {
            Response::badRequest(
                'JSON inválido'
            );
        }

        $raw['id'] = $id;

        $san =
            ResenaSanitizer::sanitizarActualizar(
                $raw
            );

        $validacion =
            ResenaValidator::validarActualizar(
                $san
            );

        if (!$validacion['success']) {

            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $resena =
                Resena::getWithReserva(
                    $id
                );

            if (!$resena) {

                Response::notFound(
                    'Reseña no encontrada'
                );
            }

            $esAdmin =
                $user->rol_id == 3;

            $esPropietario =
                $resena->reserva &&
                $resena->reserva->usuario_id ==
                $user->sub;

            if (
                !$esAdmin &&
                !$esPropietario
            ) {
                Response::forbidden(
                    'No tiene permisos para modificar esta reseña'
                );
            }

            Resena::updateResena(
                $id,
                [
                    'calificacion' =>
                        $san['calificacion'],

                    'comentario' =>
                        $san['comentario']
                ]
            );

            $resenaActualizada =
                Resena::getById($id);

            Response::success(
                $resenaActualizada,
                200,
                'Reseña actualizada exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError(
                $e->getMessage()
            );
        }
    }

    /**
     * DELETE /api/resenas/{id}
     */
    public function delete($id)
    {
        $user =
            AutenticadorMiddleware::verificar();

        $validacion =
            ResenaValidator::validarSoloId(
                $id
            );

        if (!$validacion['success']) {

            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $resena =
                Resena::getWithReserva(
                    $id
                );

            if (!$resena) {

                Response::notFound(
                    'Reseña no encontrada'
                );
            }

            $esAdmin =
                $user->rol_id == 3;

            $esPropietario =
                $resena->reserva &&
                $resena->reserva->usuario_id ==
                $user->sub;

            if (
                !$esAdmin &&
                !$esPropietario
            ) {
                Response::forbidden(
                    'No tiene permisos para eliminar esta reseña'
                );
            }

            Resena::deleteResena(
                $id
            );

            Response::success(
                null,
                200,
                'Reseña eliminada exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError(
                $e->getMessage()
            );
        }
    }
}