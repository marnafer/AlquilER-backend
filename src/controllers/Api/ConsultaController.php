<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Services\ConsultaService;
use App\Middlewares\AutenticadorMiddleware;

class ConsultaController
{
    private readonly ConsultaService $service;

    public function __construct(ConsultaService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        // Guardamos el objeto retornado por middleware
        $user = AutenticadorMiddleware::verificar();
        
        $usuarioId = (int) $user->sub;
        $rolId = (int) $user->rol_id;

        $consultas = $this->service->obtenerConsultasPorUsuario($usuarioId, $usuarioId, $rolId);

        Response::success([
            'items' => $consultas,
            'total' => count($consultas)
        ]);
    }

    public function show($id)
    {
        $user = AutenticadorMiddleware::verificar();
        
        $consulta = $this->service->obtenerConsultaAutorizada((int) $id, (int) $user->sub);

        Response::success($consulta);
    }

    public function store()
    {
        $user = AutenticadorMiddleware::verificar();
        
        $data = Request::json();
        $data['usuario_id'] = (int) $user->sub;

        $id = $this->service->crearConsulta($data);

        Response::created(
            ['id' => $id],
            'Consulta creada exitosamente'
        );
    }

    public function update($id)
    {
        $user = AutenticadorMiddleware::verificar();
        
        $this->service->actualizarConsulta((int) $id, Request::json(), (int) $user->sub);

        Response::success(
            [],
            200,
            'Consulta actualizada exitosamente'
        );
    }

    public function delete($id)
    {
        $user = AutenticadorMiddleware::verificar();
        
        $this->service->eliminarConsulta((int) $id, (int) $user->sub);

        Response::success(
            [],
            200,
            'Consulta eliminada exitosamente'
        );
    }

    public function restore($id)
    {
        $user = AutenticadorMiddleware::verificar();

        $this->service->restaurarConsulta((int) $id, (int) $user->sub);

        Response::success(
            [],
            200,
            'Consulta restaurada exitosamente'
        );
    }

    /**
     * Alias para index() para compatibilidad con tests
     */
    public function listar($request)
    {
        return $this->index($request);
    }
}