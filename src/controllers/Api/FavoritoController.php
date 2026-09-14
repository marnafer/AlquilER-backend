<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Services\FavoritoService;
use App\Middlewares\AutenticadorMiddleware;

class FavoritoController
{
    private readonly FavoritoService $service;

    public function __construct(FavoritoService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/favoritos
     *
     * Obtener todos los favoritos del usuario autenticado.
     */
    public function index(): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;
        $rolId = (int) $user->rol_id;

        $favoritos = $this->service->obtenerFavoritos(
            $usuarioId,
            $usuarioId,
            $rolId
        );

        Response::success(
            $favoritos,
            200,
            'Favoritos obtenidos correctamente'
        );
    }

    /**
     * POST /api/favoritos
     *
     * Agregar una propiedad a favoritos.
     *
     * Body:
     * {
     *     "propiedad_id": 123
     * }
     */
    public function store(): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;

        $data = Request::json();

        if (
            !isset($data['propiedad_id'])
            || !is_numeric($data['propiedad_id'])
        ) {
            Response::badRequest(
                'El campo propiedad_id es requerido y debe ser numérico'
            );
            return;
        }

        $propiedadId = (int) $data['propiedad_id'];

        $agregado = $this->service->agregarFavorito(
            $usuarioId,
            $propiedadId
        );

        if ($agregado) {
            Response::created(
                [
                    'propiedad_id' => $propiedadId,
                    'es_favorito' => true
                ],
                'Propiedad agregada a favoritos'
            );

            return;
        }

        Response::json(
            [
                'success' => false,
                'error' => 'La propiedad ya está en favoritos'
            ],
            409
        );
    }

    /**
     * GET /api/usuarios/{id}/favoritos
     *
     * Obtener los favoritos de un usuario específico.
     *
     * El Policy determina si el usuario autenticado
     * tiene permiso para consultar esos favoritos.
     */
    public function indexByUsuario($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;
        $rolId = (int) $user->rol_id;
        $usuarioConsultadoId = (int) $id;

        if ($usuarioConsultadoId <= 0) {
            Response::badRequest('ID de usuario inválido');
            return;
        }

        $favoritos = $this->service->obtenerFavoritos(
            $usuarioId,
            $usuarioConsultadoId,
            $rolId
        );

        Response::success(
            $favoritos,
            200,
            'Favoritos del usuario obtenidos correctamente'
        );
    }

    /**
     * DELETE /api/favoritos/propiedad/{propiedad_id}
     *
     * Eliminar una propiedad de favoritos.
     *
     * El Service obtiene el Favorito concreto y utiliza
     * FavoritoPolicy para comprobar la autorización.
     */
    public function deleteByPropiedad($propiedadId): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;

        if (
            !is_numeric($propiedadId)
            || (int) $propiedadId <= 0
        ) {
            Response::badRequest('ID de propiedad inválido');
            return;
        }

        $this->service->eliminarFavorito(
            $usuarioId,
            (int) $propiedadId
        );

        Response::success(
            null,
            200,
            'Propiedad eliminada de favoritos'
        );
    }

    /**
     * Alias para index() - Métodos en español para compatibilidad con tests
     */
    public function listar($request)
    {
        return $this->index($request);
    }

    /**
     * Alias para store()
     */
    public function crear($request)
    {
        return $this->store($request);
    }

    /**
     * Alias para indexByUsuario()
     */
    public function listarPorUsuario($request, $id)
    {
        return $this->indexByUsuario($request, $id);
    }

    /**
     * Alias para deleteByPropiedad()
     */
    public function eliminarPorPropiedad($request, $propiedadId)
    {
        return $this->deleteByPropiedad($request, $propiedadId);
    }

    /**
     * Eliminar un favorito específico por propiedad
     */
    public function eliminar($request, $propiedadId)
    {
        return $this->deleteByPropiedad($request, $propiedadId);
    }

    /**
     * Verificar si una propiedad es favorita del usuario
     */
    public function verificar($request, $propiedadId)
    {
        try {
            $usuarioId = $request->usuario_id ?? null;
            
            if (!$usuarioId) {
                Response::unauthorized('Usuario no autenticado');
                return;
            }
            
            if (!is_numeric($propiedadId) || $propiedadId <= 0) {
                Response::badRequest('ID de propiedad inválido');
                return;
            }
            
            $esFavorito = $this->service->esFavorito((int)$usuarioId, (int)$propiedadId);
            
            Response::success(
                ['es_favorito' => $esFavorito],
                200,
                'Verificación realizada'
            );
            
        } catch (\Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            Response::json(['success' => false, 'error' => $e->getMessage()], $status);
        }
    }
}