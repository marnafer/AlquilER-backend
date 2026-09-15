<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\PropiedadServicioService;

class PropiedadServicioController
{
    public function __construct(
        private readonly PropiedadServicioService $service
    ) {
    }

    /**
     * GET /api/propiedades/{id}/servicios
     *
     * Obtener servicios de una propiedad.
     */
    public function index($request, $propiedadId): void
    {
        try {
            AutenticadorMiddleware::verificar();

            $propiedadId = $this->validarId($propiedadId);

            $servicios = $this->service->obtenerServiciosPorPropiedad(
                $propiedadId
            );

            Response::success([
                'items' => $servicios,
                'total' => count($servicios),
            ], 200, 'Servicios de la propiedad obtenidos');

        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());

        } catch (ValidationException $e) {
            Response::validationError($e->errors());

        } catch (NotFoundException $e) {
            Response::notFound($e->getMessage());

        } catch (\Throwable $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/servicios/{id}/propiedades
     *
     * Obtener propiedades asociadas a un servicio.
     */
    public function getPropiedadesByServicio(
        $request,
        $servicioId
    ): void {
        try {
            $servicioId = $this->validarId($servicioId);

            $propiedades = $this->service->obtenerPropiedadesPorServicio(
                $servicioId
            );

            Response::success([
                'items' => $propiedades,
                'total' => count($propiedades),
            ], 200, 'Propiedades del servicio obtenidas');

        } catch (ValidationException $e) {
            Response::validationError($e->errors());

        } catch (NotFoundException $e) {
            Response::notFound($e->getMessage());

        } catch (\Throwable $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/propiedades/{id}/servicios
     *
     * Asignar un servicio a una propiedad.
     *
     * Body:
     * {
     *     "servicio_id": 1
     * }
     */
    public function store($request, $propiedadId): void
    {
        try {
            $user = AutenticadorMiddleware::verificar();

            $propiedadId = $this->validarId($propiedadId);

            $data = Request::json();

            $servicioId = $data['servicio_id'] ?? null;

            if (!is_int($servicioId)) {
                $servicioId = filter_var(
                    $servicioId,
                    FILTER_VALIDATE_INT
                );

                if ($servicioId === false) {
                    $servicioId = 0;
                }
            }

            $asignado = $this->service->asignarServicio(
                $propiedadId,
                $servicioId,
                (int) $user->sub,
                (int) $user->rol_id
            );

            if (!$asignado) {
                Response::badRequest(
                    'La propiedad ya tiene este servicio asignado'
                );
            }

            Response::created(
                [
                    'propiedad_id' => $propiedadId,
                    'servicio_id' => $servicioId,
                ],
                'Servicio asignado correctamente'
            );

        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());

        } catch (ValidationException $e) {
            Response::validationError($e->errors());

        } catch (NotFoundException $e) {
            Response::notFound($e->getMessage());

        } catch (ForbiddenException $e) {
            Response::forbidden($e->getMessage());

        } catch (BadRequestException $e) {
            Response::badRequest($e->getMessage());

        } catch (\Throwable $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/propiedades/{id}/servicios/multiple
     *
     * Asignar múltiples servicios a una propiedad.
     *
     * Body:
     * {
     *     "servicio_ids": [1, 2, 3]
     * }
     */
    public function storeMultiple($request, $propiedadId): void
    {
        try {
            $user = AutenticadorMiddleware::verificar();

            $propiedadId = $this->validarId($propiedadId);

            $data = Request::json();

            $servicioIds = $data['servicio_ids'] ?? [];

            if (!is_array($servicioIds)) {
                $servicioIds = [];
            }

            $resultados = $this->service->asignarMultiplesServicios(
                $propiedadId,
                $servicioIds,
                (int) $user->sub,
                (int) $user->rol_id
            );

            Response::success(
                $resultados,
                200,
                'Servicios asignados correctamente'
            );

        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());

        } catch (ValidationException $e) {
            Response::validationError($e->errors());

        } catch (NotFoundException $e) {
            Response::notFound($e->getMessage());

        } catch (ForbiddenException $e) {
            Response::forbidden($e->getMessage());

        } catch (BadRequestException $e) {
            Response::badRequest($e->getMessage());

        } catch (\Throwable $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/propiedades/{id}/servicios
     *
     * Sincronizar servicios de una propiedad.
     *
     * Body:
     * {
     *     "servicio_ids": [1, 2, 3]
     * }
     *
     * Un array vacío elimina todas las asociaciones.
     */
    public function update($request, $propiedadId): void
    {
        try {
            $user = AutenticadorMiddleware::verificar();

            $propiedadId = $this->validarId($propiedadId);

            $data = Request::json();

            $servicioIds = $data['servicio_ids'] ?? [];

            if (!is_array($servicioIds)) {
                $servicioIds = [];
            }

            $resultados = $this->service->sincronizarServicios(
                $propiedadId,
                $servicioIds,
                (int) $user->sub,
                (int) $user->rol_id
            );

            Response::success(
                $resultados,
                200,
                'Servicios sincronizados correctamente'
            );

        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());

        } catch (ValidationException $e) {
            Response::validationError($e->errors());

        } catch (NotFoundException $e) {
            Response::notFound($e->getMessage());

        } catch (ForbiddenException $e) {
            Response::forbidden($e->getMessage());

        } catch (BadRequestException $e) {
            Response::badRequest($e->getMessage());

        } catch (\Throwable $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * DELETE /api/propiedades/{id}/servicios/{servicio_id}
     *
     * Desasignar un servicio de una propiedad.
     */
    public function delete(
        $request,
        $propiedadId,
        $servicioId
    ): void {
        try {
            $user = AutenticadorMiddleware::verificar();

            $propiedadId = $this->validarId($propiedadId);
            $servicioId = $this->validarId($servicioId);

            $this->service->desasignarServicio(
                $propiedadId,
                $servicioId,
                (int) $user->sub,
                (int) $user->rol_id
            );

            Response::success(
                null,
                200,
                'Servicio desasignado correctamente'
            );

        } catch (UnauthorizedException $e) {
            Response::unauthorized($e->getMessage());

        } catch (ValidationException $e) {
            Response::validationError($e->errors());

        } catch (NotFoundException $e) {
            Response::notFound($e->getMessage());

        } catch (ForbiddenException $e) {
            Response::forbidden($e->getMessage());

        } catch (BadRequestException $e) {
            Response::badRequest($e->getMessage());

        } catch (\Throwable $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * Validación rápida de IDs provenientes de la URL.
     */
    private function validarId($id): int
    {
        if (
            $id === null ||
            $id === '' ||
            filter_var($id, FILTER_VALIDATE_INT) === false ||
            (int) $id <= 0
        ) {
            throw new BadRequestException(
                'El ID debe ser un entero positivo'
            );
        }

        return (int) $id;
    }

    /**
     * Aliases para compatibilidad.
     */

    public function listar($request, $propiedadId): void
    {
        $this->index($request, $propiedadId);
    }

    public function listarPropiedadesPorServicio(
        $request,
        $servicioId
    ): void {
        $this->getPropiedadesByServicio($request, $servicioId);
    }

    public function crear($request, $propiedadId): void
    {
        $this->store($request, $propiedadId);
    }

    public function crearMultiples($request, $propiedadId): void
    {
        $this->storeMultiple($request, $propiedadId);
    }

    public function sincronizar($request, $propiedadId): void
    {
        $this->update($request, $propiedadId);
    }

    public function eliminar(
        $request,
        $propiedadId,
        $servicioId
    ): void {
        $this->delete($request, $propiedadId, $servicioId);
    }
}