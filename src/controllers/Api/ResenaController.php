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
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\BadRequestException;

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

        } catch (\Throwable $exception) {
            throw $exception;
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
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $resena =
                Resena::getById($id);

            if (!$resena) {
                throw new NotFoundException(
                    'Reseña no encontrada'
                );
            }

            Response::success(
                $resena
            );

        } catch (\Throwable $exception) {
            throw $exception;
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
        throw new ValidationException(
            $validacion['errors']
        );
    }

    try {

        $propiedad = Propiedad::find($idSan);

        if (!$propiedad) {
            throw new NotFoundException(
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

    } catch (\Throwable $exception) {
        throw $exception;
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
        throw new ValidationException(
            $validacion['errors']
        );
    }

    try {

        $usuario = Usuario::find($idSan);

        if (!$usuario) {
            throw new NotFoundException(
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

    } catch (\Throwable $exception) {
        throw $exception;
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

        } catch (\Throwable $exception) {
            throw $exception;
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
            throw new BadRequestException(
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

            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $reserva =
                Reserva::find(
                    $san['reserva_id']
                );

            if (!$reserva) {

                throw new NotFoundException(
                    'Reserva no encontrada'
                );
            }

            if (
                $reserva->usuario_id !=
                $user->sub
            ) {
                throw new ForbiddenException(
                    'La reserva no pertenece al usuario autenticado'
                );
            }

            if (
                $reserva->estado !==
                'finalizada'
            ) {
                throw new BadRequestException(
                    'La reserva debe estar finalizada para poder reseñarla'
                );
            }

            if (
                Resena::existePorReserva(
                    $reserva->id
                )
            ) {
                throw new BadRequestException(
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

        } catch (\Throwable $exception) {
            throw $exception;
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
            throw new BadRequestException(
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

            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $resena =
                Resena::getWithReserva(
                    $id
                );

            if (!$resena) {

                throw new NotFoundException(
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
                throw new ForbiddenException(
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

        } catch (\Throwable $exception) {
            throw $exception;
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

            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $resena =
                Resena::getWithReserva(
                    $id
                );

            if (!$resena) {

                throw new NotFoundException(
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
                throw new ForbiddenException(
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

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}