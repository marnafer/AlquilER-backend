<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\ConsultaService;

class ConsultaController
{
    private readonly ConsultaService $service;

    public function __construct(ConsultaService $service)
    {
        $this->service = $service;
    }

    public function adminIndex(): void
    {
        $user = AutenticadorMiddleware::verificar();

        $rolId = (int) $user->rol_id;
        $filtros = $_GET ?? [];

        $consultas = $this->service->listar(
            $rolId,
            $filtros
        );

        Response::success([
            'items' => $consultas,
            'total' => count($consultas)
        ]);
    }

    public function index(): void
    {
        $user = AutenticadorMiddleware::verificar();

        $usuarioId = (int) $user->sub;
        $rolId = (int) $user->rol_id;

        $consultas = $this->service->listarPorUsuario(
            $usuarioId,
            $usuarioId,
            $rolId
        );

        Response::success([
            'items' => $consultas,
            'total' => count($consultas)
        ]);
    }

    public function indexByPropiedad($propiedadId): void
    {
        $user = AutenticadorMiddleware::verificar();

        $consultas = $this->service->listarPorPropiedad(
            (int) $propiedadId,
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::success([
            'items' => $consultas,
            'total' => count($consultas)
        ]);
    }

    public function indexByUsuario($usuarioId): void
    {
        $user = AutenticadorMiddleware::verificar();

        $consultas = $this->service->listarPorUsuario(
            (int) $usuarioId,
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::success([
            'items' => $consultas,
            'total' => count($consultas)
        ]);
    }

    public function show($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $consulta = $this->service->obtenerAutorizada(
            (int) $id,
            (int) $user->sub
        );

        Response::success($consulta);
    }

    public function store(): void
    {
        $user = AutenticadorMiddleware::verificar();

        $data = Request::json();
        $data['usuario_id'] = (int) $user->sub;

        $id = $this->service->crear($data);

        Response::created(
            ['id' => $id],
            'Consulta creada exitosamente'
        );
    }

    public function update($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $this->service->actualizar(
            (int) $id,
            Request::json(),
            (int) $user->sub
        );

        Response::success(
            [],
            200,
            'Consulta actualizada exitosamente'
        );
    }

    public function delete($id): void
    {
        $user = AutenticadorMiddleware::verificar();

        $this->service->eliminar(
            (int) $id,
            (int) $user->sub
        );

        Response::success(
            [],
            200,
            'Consulta eliminada exitosamente'
        );
    }
}