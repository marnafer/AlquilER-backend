<?php

namespace App\Controllers\Api;

use App\Models\Propiedad;
use App\Helpers\Response;
use App\Sanitizers\PropiedadSanitizer;
use App\Validators\PropiedadValidator;
use App\Middlewares\AutenticadorMiddleware;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\BadRequestException;

class PropiedadController
{
    /**
     * GET /api/propiedades
     */
    public function index()
    {
        try {

            $propiedades = Propiedad::all();

            Response::success([
                'items' => $propiedades,
                'total' => $propiedades->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/propiedades/{id}
     */
    public function show($id)
    {
        $idSan = PropiedadSanitizer::sanitizarIdPropiedad($id);

        $validacion = PropiedadValidator::validarSoloIdPropiedad(
            $idSan
        );

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

            Response::success($propiedad);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * POST /api/propiedades
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

        $san = PropiedadSanitizer::sanitizarPropiedad(
            $raw
        );

        $validacion = PropiedadValidator::validarCrearPropiedad(
            $san
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $san['usuario_id'] = $user->sub;

            $propiedad = Propiedad::create(
                $san
            );

            Response::created(
                $propiedad->toArray(),
                'Propiedad creada exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * PUT /api/propiedades/{id}
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

        $san = PropiedadSanitizer::sanitizarPropiedad(
            $raw
        );

        $validacion = PropiedadValidator::validarActualizarPropiedad(
            $san
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $propiedad = Propiedad::find(
                $san['id']
            );

            if (!$propiedad) {
                throw new NotFoundException(
                    'Propiedad no encontrada'
                );
            }

            if (
                (int) $user->rol_id !== 2 &&
                (int) $propiedad->usuario_id !== (int) $user->sub
            ) {
                throw new ForbiddenException(
                    'No tienes permiso para modificar esta propiedad'
                );
            }

            $propiedad->update([
                'titulo' => $san['titulo'],
                'descripcion' => $san['descripcion'],
                'precio' => $san['precio'],
                'expensas' => $san['expensas'],
                'direccion' => $san['direccion'],
                'cantidad_ambientes' => $san['cantidad_ambientes'],
                'cantidad_dormitorios' => $san['cantidad_dormitorios'],
                'cantidad_banos' => $san['cantidad_banos'],
                'capacidad' => $san['capacidad'],
                'disponible' => $san['disponible'],
                'categoria_id' => $san['categoria_id'],
                'localidad_id' => $san['localidad_id']
            ]);

            Response::success($propiedad->fresh());

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * DELETE /api/propiedades/{id}
     */
    public function delete($id)
    {
        $user = AutenticadorMiddleware::verificar();

        $idSan = PropiedadSanitizer::sanitizarIdPropiedad(
            $id
        );

        $validacion = PropiedadValidator::validarSoloIdPropiedad(
            $idSan
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $propiedad = Propiedad::find(
                $idSan
            );

            if (!$propiedad) {
                throw new NotFoundException(
                    'Propiedad no encontrada'
                );
            }

            if (
                (int) $user->rol_id !== 2 &&
                (int) $propiedad->usuario_id !== (int) $user->sub
            ) {
                throw new ForbiddenException(
                    'No tienes permiso para eliminar esta propiedad'
                );
            }

            $propiedad->delete();

            Response::success(
                [],
                200,
                'Propiedad eliminada exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * PATCH /api/propiedades/{id}/restaurar
     */
    public function restore($id)
    {
        $user = AutenticadorMiddleware::verificar();

        $idSan = PropiedadSanitizer::sanitizarIdPropiedad(
            $id
        );

        $validacion = PropiedadValidator::validarSoloIdPropiedad(
            $idSan
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        try {

            $propiedad = Propiedad::withTrashed()
                ->find($idSan);

            if (!$propiedad) {
                throw new NotFoundException(
                    'Propiedad no encontrada'
                );
            }

            if (
                (int) $user->rol_id !== 2 &&
                (int) $propiedad->usuario_id !== (int) $user->sub
            ) {
                throw new ForbiddenException(
                    'No tienes permiso para restaurar esta propiedad'
                );
            }

            if ($propiedad->deleted_at === null) {
                throw new BadRequestException(
                    'La propiedad no está eliminada'
                );
            }

            $propiedad->restore();

            Response::success(
                $propiedad->fresh(),
                200,
                'Propiedad restaurada exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}