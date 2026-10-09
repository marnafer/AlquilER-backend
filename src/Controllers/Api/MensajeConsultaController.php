<?php

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Services\MensajeConsultaService;
use App\Middlewares\AutenticadorMiddleware;

class MensajeConsultaController
{
    private readonly MensajeConsultaService $service;

    public function __construct(MensajeConsultaService $service)
    {
        $this->service = $service;
    }

    public function index($consultaId): void
    {
        $user = AutenticadorMiddleware::verificar();

        $parametros = $_GET ?? [];

        $resultado = $this->service->obtenerHistorial(
            (int) $consultaId,
            (int) $user->sub,
            (int) $user->rol_id,
            $parametros['antes_de_id'] ?? null,
            $parametros['despues_de_id'] ?? null,
            $parametros['limite'] ?? null
        );

        Response::success($resultado);
    }

    public function store($consultaId)
    {
        $user = AutenticadorMiddleware::verificar();
        
        $data = Request::json();
        $data['consulta_id'] = (int) $consultaId;

        $mensaje = $this->service->crearMensaje(
            $data,
            (int) $user->sub,
            (int) $user->rol_id
        );

        Response::created(
            $mensaje,
            'Mensaje enviado correctamente'
        );
    }
}