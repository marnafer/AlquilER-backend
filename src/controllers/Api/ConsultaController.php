<?php

namespace App\Controllers\Api;

use App\Models\Consulta;
use App\Models\Propiedad;
use App\Sanitizers\ConsultaSanitizer;
use App\Validators\ConsultaValidator;
use App\Middlewares\AutenticadorMiddleware;
use App\Helpers\Response;

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

        } catch (\Exception $e) {

            Response::serverError();
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
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $consulta = Consulta::find($idSan);

            if (!$consulta) {
                Response::notFound(
                    'Consulta no encontrada'
                );
            }

            if (
                $user->rol_id != 3 &&
                $consulta->usuario_id != $user->sub
            ) {
                Response::forbidden(
                    'No autorizado'
                );
            }

            Response::success([
                'consulta' => $consulta
            ]);

        } catch (\Exception $e) {

            Response::serverError();
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
                Response::notFound(
                    'Propiedad no encontrada'
                );
            }

            if (
                $user->rol_id != 3 &&
                $propiedad->usuario_id != $user->sub
            ) {
                Response::forbidden(
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

        } catch (\Exception $e) {

            Response::serverError();
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
                Response::forbidden(
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

        } catch (\Exception $e) {

            Response::serverError();
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
            Response::badRequest(
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

        } catch (\Exception $e) {

            Response::serverError();
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
            Response::badRequest(
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
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $consulta = Consulta::find(
                $san['id']
            );

            if (!$consulta) {
                Response::notFound(
                    'Consulta no encontrada'
                );
            }

            if (
                $user->rol_id != 3 &&
                $consulta->usuario_id != $user->sub
            ) {
                Response::forbidden(
                    'No autorizado'
                );
            }

            $consulta->update([
                'mensaje' => $san['mensaje']
            ]);

            Response::success([
                'data' => $consulta->fresh()
            ]);

        } catch (\Exception $e) {

            Response::serverError();
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
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $consulta = Consulta::find(
                $idSan
            );

            if (!$consulta) {
                Response::notFound(
                    'Consulta no encontrada'
                );
            }

            $consulta->delete();

            Response::success(
                [],
                200,
                'Consulta eliminada exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError();
        }
    }
}