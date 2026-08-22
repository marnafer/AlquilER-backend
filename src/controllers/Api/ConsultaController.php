<?php

namespace App\Controllers\Api;

use App\Models\Consulta;
use App\Models\Propiedad;
use App\Sanitizers\ConsultaSanitizer;
use App\Validators\ConsultaValidator;
use App\Middlewares\AutenticadorMiddleware;
use App\Helpers\Response;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\BadRequestException;

class ConsultaController
{
    /**
     * GET /api/consultas
     */
    public function index()
    {
        AutenticadorMiddleware::soloAdmin();

        try {

            $consultas = Consulta::all();

            Response::success([
                'items' => $consultas,
                'total' => $consultas->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/consultas/{id}
     */
    public function show($id)
    {
        $user = AutenticadorMiddleware::verificar();

        $idSan = ConsultaSanitizer::sanitizarId($id);

        $validacion = ConsultaValidator::validarSoloIdConsulta(
            $idSan
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $consulta = Consulta::find($idSan);

            if (!$consulta) {
                throw new NotFoundException(
                    'Consulta no encontrada'
                );
            }

            if (
                $user->rol_id != 3 &&
                $consulta->usuario_id != $user->sub
            ) {
                throw new ForbiddenException(
                    'No autorizado'
                );
            }

            Response::success([
                'consulta' => $consulta
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/consultas/propiedad/{id}
     */
    public function indexByPropiedad($propiedadId)
    {
        $user = AutenticadorMiddleware::verificar();

        $idSan = ConsultaSanitizer::sanitizarId(
            $propiedadId
        );

        try {

            $propiedad = Propiedad::find($idSan);

            if (!$propiedad) {
                throw new NotFoundException(
                    'Propiedad no encontrada'
                );
            }

            if (
                $user->rol_id != 3 &&
                $propiedad->usuario_id != $user->sub
            ) {
                throw new ForbiddenException(
                    'No autorizado'
                );
            }

            $consultas = Consulta::where(
                'propiedad_id',
                $idSan
            )->get();

            Response::success([
                'items' => $consultas,
                'total' => $consultas->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/consultas/usuario/{id}
     */
    public function indexByUsuario($usuarioId)
    {
        $user = AutenticadorMiddleware::verificar();

        $idSan = ConsultaSanitizer::sanitizarId(
            $usuarioId
        );

        try {

            if (
                $user->rol_id != 3 &&
                $user->sub != $idSan
            ) {
                throw new ForbiddenException(
                    'No autorizado'
                );
            }

            $consultas = Consulta::where(
                'usuario_id',
                $idSan
            )->get();

            Response::success([
                'items' => $consultas,
                'total' => $consultas->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * POST /api/consultas
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

        $san = ConsultaSanitizer::sanitizarConsulta(
            $raw
        );

        $validacion = ConsultaValidator::validarCrearConsulta(
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

            $consulta = Consulta::create([
                'propiedad_id' => $san['propiedad_id'],
                'usuario_id' => $user->sub,
                'mensaje' => $san['mensaje'],
                'fecha_consulta' => date(
                    'Y-m-d H:i:s'
                )
            ]);

            Response::created(
                $consulta->toArray(),
                'Consulta creada exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * PUT /api/consultas/{id}
     */
    public function update($id)
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

        $raw['id'] = $id;

        $san = ConsultaSanitizer::sanitizarConsulta(
            $raw
        );

        $validacion = ConsultaValidator::validarActualizarConsulta(
            $san
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $consulta = Consulta::find(
                $san['id']
            );

            if (!$consulta) {
                throw new NotFoundException(
                    'Consulta no encontrada'
                );
            }

            if (
                $user->rol_id != 3 &&
                $consulta->usuario_id != $user->sub
            ) {
                throw new ForbiddenException(
                    'No autorizado'
                );
            }

            $consulta->update([
                'mensaje' => $san['mensaje']
            ]);

            Response::success([
                'data' => $consulta->fresh()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * DELETE /api/consultas/{id}
     */
    public function delete($id)
    {
        AutenticadorMiddleware::soloAdmin();

        $idSan = ConsultaSanitizer::sanitizarId(
            $id
        );

        $validacion = ConsultaValidator::validarSoloIdConsulta(
            $idSan
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $consulta = Consulta::find(
                $idSan
            );

            if (!$consulta) {
                throw new NotFoundException(
                    'Consulta no encontrada'
                );
            }

            $consulta->delete();

            Response::success(
                [],
                200,
                'Consulta eliminada exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}