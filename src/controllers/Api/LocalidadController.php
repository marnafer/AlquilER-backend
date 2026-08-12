<?php

namespace App\Controllers\Api;

use App\Models\Localidad;
use App\Models\Provincia;
use App\Sanitizers\LocalidadSanitizer;
use App\Validators\LocalidadValidator;
use App\Helpers\Response;

class LocalidadController
{
    /**
     * GET /api/localidades
     */
    public function index()
    {
        try {

            $localidades = Localidad::all();

            Response::success([
                'items' => $localidades,
                'total' => $localidades->count()
            ]);

        } catch (\Exception $e) {

            Response::serverError();
        }
    }

    /**
     * GET /api/localidades/{id}
     */
    public function show($id)
    {
        $idSan = LocalidadSanitizer::sanitizarIdLocalidad($id);

        $validacion = LocalidadValidator::validarSoloIdLocalidad($idSan);

        if (!$validacion['success']) {
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $localidad = Localidad::find($idSan);

            if (!$localidad) {
                Response::notFound('Localidad no encontrada');
            }

            Response::success([
                'data' => $localidad
            ]);

        } catch (\Exception $e) {

            Response::serverError();
        }
    }

    /**
     * POST /api/localidades
     */
    public function store()
    {
        $raw = json_decode(file_get_contents('php://input'), true);

        if (!is_array($raw)) {
            Response::badRequest('JSON inválido');
        }

        $san = LocalidadSanitizer::sanitizarLocalidad($raw);

        $validacion = LocalidadValidator::validarCrearLocalidad($san);

        if (!$validacion['success']) {
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            if (
                Localidad::where('nombre', $san['nombre'])
                    ->exists()
            ) {
                Response::badRequest(
                    'Ya existe una localidad con ese nombre'
                );
            }

            $localidad = Localidad::create($san);

            Response::created(
                $localidad->toArray(),
                'Localidad creada exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError();
        }
    }

    /**
     * PUT /api/localidades/{id}
     */
   public function update($id)
    {
        $raw = json_decode(file_get_contents('php://input'), true);

        if (!is_array($raw)) {
            Response::badRequest('JSON inválido');
        }

        $raw['id'] = $id;

        $san = LocalidadSanitizer::sanitizarLocalidad($raw);

        $validacion = LocalidadValidator::validarActualizarLocalidad($san);

        if (!$validacion['success']) {
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $localidad = Localidad::find($san['id']);

        if (!$localidad) {
            Response::notFound(
                'Localidad no encontrada'
            );
        }

        if (
            Localidad::where('nombre', $san['nombre'])
                ->where('id', '!=', $san['id'])
                ->exists()
        ) {
            Response::badRequest(
                'Ya existe otra localidad con ese nombre'
            );
        }

        $localidad->update([
            'nombre' => $san['nombre'],
            'codigo_postal' => $san['codigo_postal'],
            'provincia_id' => $san['provincia_id']
        ]);

        Response::success([
            'data' => $localidad->fresh()
        ]);

        } catch (\Exception $e) {

            Response::serverError();
        }
    }

    /**
     * DELETE /api/localidades/{id}
     */
    public function delete($id)
    {
        $idSan = LocalidadSanitizer::sanitizarIdLocalidad($id);

        $validacion = LocalidadValidator::validarSoloIdLocalidad($idSan);

        if (!$validacion['success']) {
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $localidad = Localidad::find($idSan);

            if (!$localidad) {
                Response::notFound(
                    'Localidad no encontrada'
                );
            }

            $localidad->delete();

            Response::json([
                'success' => true,
                'message' => 'Localidad eliminada exitosamente'
            ], 200);

        } catch (\Exception $e) {

            Response::serverError();
        }
    }
}