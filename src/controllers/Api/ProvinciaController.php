<?php

namespace App\Controllers\Api;

use App\Models\Provincia;
use App\Validators\ProvinciaValidator;
use App\Sanitizers\ProvinciaSanitizer;
use App\Helpers\Response;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;

class ProvinciaController
{
    /**
     * GET /api/provincias
     */
    public function index()
    {
        try {

            $provincias = Provincia::all();

            Response::success([
                'items' => $provincias,
                'total' => $provincias->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/provincias/con-localidades
     */
    public function indexWithCount()
    {
        try {

            $provincias = Provincia::withCount('localidades')->get();

            Response::success([
                'items' => $provincias,
                'total' => $provincias->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/provincias/{id}
     */
    public function show($id)
    {
        $idSan = ProvinciaSanitizer::sanitizarIdProvincia($id);

        $validacion = ProvinciaValidator::validarSoloIdProvincia($idSan);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $provincia = Provincia::find($idSan);

            if (!$provincia) {
                throw new NotFoundException('Provincia no encontrada');
            }

            Response::success($provincia);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * POST /api/provincias
     */
    public function store()
    {
        $raw = json_decode(file_get_contents('php://input'), true);

        if (!is_array($raw)) {
            throw new BadRequestException('JSON inválido');
        }

        $san = ProvinciaSanitizer::sanitizarProvincia($raw);

        $validacion = ProvinciaValidator::validarCrearProvincia($san);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            if (Provincia::where('nombre', $san['nombre'])->exists()) {
                throw new BadRequestException(
                    'Ya existe una provincia con este nombre'
                );
            }

            $provincia = Provincia::create($san);

            Response::created(
                $provincia->toArray(),
                'Provincia creada exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * PUT /api/provincias/{id}
     */
    public function update($id)
    {
        $raw = json_decode(file_get_contents('php://input'), true);

        if (!is_array($raw)) {
            throw new BadRequestException('JSON inválido');
        }

        $raw['id'] = $id;

        $san = ProvinciaSanitizer::sanitizarProvincia($raw);

        $validacion = ProvinciaValidator::validarActualizarProvincia($san);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $provincia = Provincia::find($san['id']);

            if (!$provincia) {
                throw new NotFoundException('Provincia no encontrada');
            }

            if (
                Provincia::where('nombre', $san['nombre'])
                    ->where('id', '!=', $san['id'])
                    ->exists()
            ) {
                throw new BadRequestException(
                    'Ya existe otra provincia con este nombre'
                );
            }

            $provincia->update($san);

            Response::success(
                $provincia,
                200,
                'Provincia actualizada exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * DELETE /api/provincias/{id}
     */
    public function delete($id)
    {
        $idSan = ProvinciaSanitizer::sanitizarIdProvincia($id);

        $validacion = ProvinciaValidator::validarSoloIdProvincia($idSan);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $provincia = Provincia::find($idSan);

            if (!$provincia) {
                throw new NotFoundException('Provincia no encontrada');
            }

            if ($provincia->localidades()->exists()) {
                throw new BadRequestException(
                    'No se puede eliminar la provincia porque tiene localidades asociadas'
                );
            }

            $provincia->delete();

            Response::success(
                null,
                200,
                'Provincia eliminada exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}