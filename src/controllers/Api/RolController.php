<?php

namespace App\Controllers\Api;

use App\Models\Rol;
use App\Sanitizers\RolSanitizer;
use App\Validators\RolValidator;
use App\Helpers\Response;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;

class RolController
{
    /**
     * GET /api/roles
     */
    public function index()
    {
        try {

            $roles = Rol::all();

            Response::success([
                'items' => $roles,
                'total' => $roles->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/roles/con-usuarios
     */
    public function indexWithCount()
    {
        try {

            $roles = Rol::withCount('usuarios')->get();

            Response::success([
                'items' => $roles,
                'total' => $roles->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/roles/{id}
     */
    public function show($id)
    {
        $idSan = RolSanitizer::sanitizarIdRol($id);

        $validacion = RolValidator::validarSoloIdRol($idSan);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $rol = Rol::find($idSan);

            if (!$rol) {
                throw new NotFoundException(
                    'Rol no encontrado'
                );
            }

            Response::success([
                'data' => $rol
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * POST /api/roles
     */
    public function store()
    {
        $raw = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($raw)) {
            throw new BadRequestException(
                'JSON inválido'
            );
        }

        $san = RolSanitizer::sanitizarRol($raw);

        $validacion = RolValidator::validarCrearRol($san);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            if (Rol::existsByNombre($san['nombre'])) {
                throw new BadRequestException(
                    'Ya existe un rol con ese nombre'
                );
            }

            $rol = Rol::create($san);

            Response::created(
                $rol->toArray(),
                'Rol creado exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * PUT /api/roles/{id}
     */
    public function update($id)
    {
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

        $san = RolSanitizer::sanitizarRol($raw);

        $validacion = RolValidator::validarActualizarRol($san);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $rol = Rol::find($san['id']);

            if (!$rol) {
                throw new NotFoundException(
                    'Rol no encontrado'
                );
            }

            if (Rol::existsByNombre(
                $san['nombre'],
                $san['id']
            )) {
                throw new BadRequestException(
                    'Ya existe otro rol con ese nombre'
                );
            }

            $rol->update([
                'nombre' => $san['nombre']
            ]);

            Response::success([
                'data' => $rol->fresh()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * DELETE /api/roles/{id}
     */
    public function delete($id)
    {
        $idSan = RolSanitizer::sanitizarIdRol($id);

        $validacion = RolValidator::validarSoloIdRol($idSan);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $rol = Rol::find($idSan);

            if (!$rol) {
                throw new NotFoundException(
                    'Rol no encontrado'
                );
            }

            if ($rol->hasUsuarios()) {
                throw new BadRequestException(
                    'No se puede eliminar el rol porque tiene usuarios asociados'
                );
            }

            $rol->delete();

            Response::success([
                'message' => 'Rol eliminado exitosamente'
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}