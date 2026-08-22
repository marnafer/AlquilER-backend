<?php

namespace App\Controllers\Api;

use App\Models\Localidad;
use App\Sanitizers\LocalidadSanitizer;
use App\Validators\LocalidadValidator;
use App\Helpers\Response;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;

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

        } catch (\Throwable $exception) {
            throw $exception;
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
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $localidad = Localidad::find($idSan);

            if (!$localidad) {
                throw new NotFoundException('Localidad no encontrada');
            }

            Response::success([
                'data' => $localidad
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * POST /api/localidades
     */
    public function store()
    {
        $raw = json_decode(file_get_contents('php://input'), true);

        if (!is_array($raw)) {
            throw new BadRequestException('JSON inválido');
        }

        $san = LocalidadSanitizer::sanitizarLocalidad($raw);

        $validacion = LocalidadValidator::validarCrearLocalidad($san);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            if (
                Localidad::where('nombre', $san['nombre'])
                    ->exists()
            ) {
                throw new BadRequestException(
                    'Ya existe una localidad con ese nombre'
                );
            }

            $localidad = Localidad::create($san);

            Response::created(
                $localidad->toArray(),
                'Localidad creada exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * PUT /api/localidades/{id}
     */
   public function update($id)
    {
        $raw = json_decode(file_get_contents('php://input'), true);

        if (!is_array($raw)) {
            throw new BadRequestException('JSON inválido');
        }

        $raw['id'] = $id;

        $san = LocalidadSanitizer::sanitizarLocalidad($raw);

        $validacion = LocalidadValidator::validarActualizarLocalidad($san);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $localidad = Localidad::find($san['id']);

        if (!$localidad) {
            throw new NotFoundException(
                'Localidad no encontrada'
            );
        }

        if (
            Localidad::where('nombre', $san['nombre'])
                ->where('id', '!=', $san['id'])
                ->exists()
        ) {
            throw new BadRequestException(
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

        } catch (\Throwable $exception) {
            throw $exception;
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
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $localidad = Localidad::find($idSan);

            if (!$localidad) {
                throw new NotFoundException(
                    'Localidad no encontrada'
                );
            }

            $localidad->delete();

            Response::json([
                'success' => true,
                'message' => 'Localidad eliminada exitosamente'
            ], 200);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}