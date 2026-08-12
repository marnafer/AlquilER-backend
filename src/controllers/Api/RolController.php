<?php

namespace App\Controllers\Api;

use App\Models\Rol;
use App\Sanitizers\RolSanitizer;
use App\Validators\RolValidator;
use App\Helpers\Response;

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

        } catch (\Exception $e) {

            Response::serverError();
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

        } catch (\Exception $e) {

            Response::serverError();
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
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $rol = Rol::find($idSan);

            if (!$rol) {
                Response::notFound(
                    'Rol no encontrado'
                );
            }

            Response::success([
                'data' => $rol
            ]);

        } catch (\Exception $e) {

            Response::serverError();
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
            Response::badRequest(
                'JSON inválido'
            );
        }

        $san = RolSanitizer::sanitizarRol($raw);

        $validacion = RolValidator::validarCrearRol($san);

        if (!$validacion['success']) {
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            if (Rol::existsByNombre($san['nombre'])) {
                Response::badRequest(
                    'Ya existe un rol con ese nombre'
                );
            }

            $rol = Rol::create($san);

            Response::created(
                $rol->toArray(),
                'Rol creado exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError();
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
            Response::badRequest(
                'JSON inválido'
            );
        }

        $raw['id'] = $id;

        $san = RolSanitizer::sanitizarRol($raw);

        $validacion = RolValidator::validarActualizarRol($san);

        if (!$validacion['success']) {
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $rol = Rol::find($san['id']);

            if (!$rol) {
                Response::notFound(
                    'Rol no encontrado'
                );
            }

            if (Rol::existsByNombre(
                $san['nombre'],
                $san['id']
            )) {
                Response::badRequest(
                    'Ya existe otro rol con ese nombre'
                );
            }

            $rol->update([
                'nombre' => $san['nombre']
            ]);

            Response::success([
                'data' => $rol->fresh()
            ]);

        } catch (\Exception $e) {

            Response::serverError();
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
            Response::validationError(
                $validacion['errors']
            );
        }

        try {

            $rol = Rol::find($idSan);

            if (!$rol) {
                Response::notFound(
                    'Rol no encontrado'
                );
            }

            if ($rol->hasUsuarios()) {
                Response::badRequest(
                    'No se puede eliminar el rol porque tiene usuarios asociados'
                );
            }

            $rol->delete();

            Response::success([
                'message' => 'Rol eliminado exitosamente'
            ]);

        } catch (\Exception $e) {

            Response::serverError();
        }
    }
}