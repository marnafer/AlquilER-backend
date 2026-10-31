<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\ConsultaService;

class ConsultaController
{
    public function __construct(
        private readonly ConsultaService $service,
        private readonly Authenticator $auth
    ) {
    }

    public function adminIndex(Request $request): Response
    {
        $user = $this->auth->verificar($request);

        $consultas = $this->service->listar(
            (int) $user->rol_id,
            $request->queryParams()
        );

        return Response::success([
            'items' => $consultas,
            'total' => count($consultas)
        ]);
    }

    public function index(Request $request): Response
    {
        $user = $this->auth->verificar($request);

        $consultas = $this->service->listarPorUsuario(
            (int) $user->sub,
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::success([
            'items' => $consultas,
            'total' => count($consultas)
        ]);
    }

    public function indexByPropiedad(Request $request, $propiedadId): Response
    {
        $user = $this->auth->verificar($request);

        $consultas = $this->service->listarPorPropiedad(
            (int) $propiedadId,
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::success([
            'items' => $consultas,
            'total' => count($consultas)
        ]);
    }

    public function indexByUsuario(Request $request, $usuarioId): Response
    {
        $user = $this->auth->verificar($request);

        $consultas = $this->service->listarPorUsuario(
            (int) $usuarioId,
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::success([
            'items' => $consultas,
            'total' => count($consultas)
        ]);
    }

    public function indexRecibidas(Request $request): Response
    {
        $user = $this->auth->verificar($request);

        $consultas = $this->service->listarRecibidasPorPropietario(
            (int) $user->sub
        );

        return Response::success([
            'items' => $consultas,
            'total' => count($consultas),
        ]);
    }

    public function show(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $consulta = $this->service->obtenerAutorizada(
            (int) $id,
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::success($consulta);
    }

    public function store(Request $request): Response
    {
        $user = $this->auth->verificar($request);

        $data = $request->json();
        $data['usuario_id'] = (int) $user->sub;

        $id = $this->service->crear($data);

        return Response::created(
            ['id' => $id],
            'Consulta creada exitosamente'
        );
    }

    public function update(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $this->service->actualizar(
            (int) $id,
            $request->json(),
            (int) $user->sub
        );

        return Response::success(
            [],
            200,
            'Consulta actualizada exitosamente'
        );
    }

    public function delete(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $this->service->eliminar(
            (int) $id,
            (int) $user->sub
        );

        return Response::success(
            [],
            200,
            'Consulta eliminada exitosamente'
        );
    }

    public function restore(Request $request, $id): Response
    {
        $user = $this->auth->verificar($request);

        $this->service->restaurar(
            (int) $id,
            (int) $user->sub
        );

        return Response::success(
            [],
            200,
            'Consulta restaurada exitosamente'
        );
    }
}
