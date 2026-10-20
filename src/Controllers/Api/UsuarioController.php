<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\UsuarioService;

class UsuarioController
{
    public function __construct(
        private readonly UsuarioService $service,
        private readonly Authenticator $auth
    ) {
    }

    /**
     * GET /api/usuarios
     */
    public function index(Request $request): Response
    {
        $this->auth->soloAdmin($request);

        return Response::success(
            $this->service->listar($this->filtrosPapelera($request))
        );
    }

    /**
     * GET /api/usuarios/{id}
     */
    public function show(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        return Response::success(
            $this->service->obtenerAutorizado(
                $id,
                (int) $user->sub,
                (int) $user->rol_id
            )
        );
    }

    /**
     * GET /api/usuarios/me
     */
    public function profile(Request $request): Response
    {
        $user = $this->auth->verificar($request);

        return Response::success(
            $this->service->obtenerConRol((int) $user->sub)
        );
    }

    /**
     * GET /api/usuarios/{id}/perfil-publico
     */
    public function publicProfile(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        return Response::success(
            $this->service->obtenerPerfilPublico(
                $id,
                (int) $user->sub,
                (int) $user->rol_id
            )
        );
    }

    /**
     * POST /api/admin/usuarios
     */
    public function storeAdmin(Request $request): Response
    {
        $admin = $this->auth->soloAdmin($request);

        $usuario = $this->service->crearDesdeAdmin(
            $request->json(),
            (int) $admin->sub
        );

        return Response::created(
            $usuario,
            'Usuario creado correctamente'
        );
    }

    /**
     * PUT /api/usuarios/{id}
     */
    public function update(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $this->service->actualizar(
            $id,
            $request->json(),
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::success([], 200, 'Usuario actualizado correctamente');
    }

    /**
     * DELETE /api/usuarios/{id}
     */
    public function delete(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $this->service->eliminar(
            $id,
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::success([], 200, 'Usuario eliminado');
    }

    /**
     * POST /api/usuarios/{id}/restaurar
     */
    public function restore(Request $request, $id): Response
    {
        $this->auth->soloAdmin($request);

        $this->service->restaurar($id);

        return Response::success(
            [],
            200,
            'Usuario restaurado correctamente'
        );
    }

    private function filtrosPapelera(Request $request): array
    {
        $filtros = [];

        if (($valor = $request->query('solo_eliminados')) !== null) {
            $filtros['solo_eliminados'] = filter_var(
                $valor,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if (($valor = $request->query('incluir_eliminados')) !== null) {
            $filtros['incluir_eliminados'] = filter_var(
                $valor,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        return $filtros;
    }
}
