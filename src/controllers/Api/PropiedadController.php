<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\PropiedadService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use App\Exceptions\UnauthorizedException;


class PropiedadController
{

    private readonly PropiedadService $service;

    public function __construct(PropiedadService $service)
    {
        $this->service = $service;
    }

    /**
     * Valida que un ID sea numérico válido
     */
    private function validateId($id): bool
    {
        return is_numeric($id) && (int)$id > 0;
    }

    /**
     * GET /api/propiedades
     */
    public function index(): void
    {
        try {
            Response::success(
                $this->service->listar()
            );
        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());
        } catch (\Exception $e) {
            Response::serverError('Error al listar propiedades');
        }
    }

    /**
     * GET /api/propiedades/mis-propiedades
     * Devuelve solo las propiedades del usuario autenticado.
     */
    public function misPropiedades(): void
    {
        try {
            $user = AutenticadorMiddleware::verificar();

            Response::success(
                $this->service->misPropiedades((int) $user->sub)
            );
        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());
        } catch (\Exception $e) {
            Response::serverError('Error al listar tus propiedades');
        }
    }

    /**
     * GET /api/propiedades/{id}
     */
    public function show($id): void
    {
        if (!$this->validateId($id)) {
            Response::badRequest('ID de propiedad inválido');
            return;
        }

        try {
            Response::success(
                $this->service->obtener((int)$id)
            );
        } catch (NotFoundException $e) {
            Response::notFound($e->getMessage());
        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());
        }
    }

    /**
     * POST /api/propiedades
     */
    public function store(): void
    {
        try {
            $user = AutenticadorMiddleware::verificar();

            $propiedad = $this->service->crear(
                Request::json(),
                (int) $user->sub
            );

            Response::created(
                $propiedad,
                'Propiedad creada exitosamente'
            );
        } catch (ValidationException $e) {
            Response::validationError($e->errors());
        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());
        } catch (\Exception $e) {
            Response::serverError('Error al crear propiedad');
        }
    }

    /**
     * PUT /api/propiedades/{id}
     */
    public function update($id): void
    {
        if (!$this->validateId($id)) {
            Response::badRequest('ID de propiedad inválido');
            return;
        }

        try {
            $user = AutenticadorMiddleware::verificar();

            $this->service->actualizar(
                (int) $user->sub,
                (int) $user->rol_id,
                (int) $id,
                Request::json()
            );

            Response::success(
                [],
                200,
                'Propiedad actualizada exitosamente'
            );
        } catch (NotFoundException $e) {
            Response::notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            Response::forbidden($e->getMessage());
        } catch (ValidationException $e) {
            Response::validationError($e->errors());
        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());
        }
    }

    /**
     * DELETE /api/propiedades/{id}
     */
    public function delete($id): void
    {
        if (!$this->validateId($id)) {
            Response::badRequest('ID de propiedad inválido');
            return;
        }

        try {
            $user = AutenticadorMiddleware::verificar();

            $this->service->eliminar(
                (int) $user->sub,
                (int) $user->rol_id,
                (int) $id
            );

            Response::success(
                [],
                200,
                'Propiedad eliminada exitosamente'
            );
        } catch (NotFoundException $e) {
            Response::notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            Response::forbidden($e->getMessage());
        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());
        }
    }

    /**
     * PATCH /api/propiedades/{id}/restaurar
     */
    public function restore($id): void
    {
        if (!$this->validateId($id)) {
            Response::badRequest('ID de propiedad inválido');
            return;
        }

        try {
            $user = AutenticadorMiddleware::verificar();

            $this->service->restaurar(
                (int) $user->sub,
                (int) $user->rol_id,
                (int) $id
            );

            Response::success(
                [],
                200,
                'Propiedad restaurada exitosamente'
            );
        } catch (NotFoundException $e) {
            Response::notFound($e->getMessage());
        } catch (ForbiddenException $e) {
            Response::forbidden($e->getMessage());
        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());
        }
    }

    /**
     * Métodos alias en español para compatibilidad con tests
     */
    public function listar()
    {
        return $this->index();
    }

    public function crear()
    {
        return $this->store();
    }

    public function obtener($id)
    {
        return $this->show($id);
    }

    public function actualizar($id)
    {
        return $this->update($id);
    }

    public function eliminar($id)
    {
        return $this->delete($id);
    }

    public function restaurar($id)
    {
        return $this->restore($id);
    }
}