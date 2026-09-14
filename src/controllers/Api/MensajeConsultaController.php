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

    public function index($consultaId)
    {
        $user = AutenticadorMiddleware::verificar();
        
        $mensajes = $this->service->obtenerHistorial((int) $consultaId, (int) $user->sub);

        Response::success([
            'items' => $mensajes,
            'total' => count($mensajes)
        ]);
    }

    public function store($consultaId)
    {
        $user = AutenticadorMiddleware::verificar();
        
        $data = Request::json();
        $data['consulta_id'] = (int) $consultaId;

        $mensaje = $this->service->crearMensaje($data, (int) $user->sub);

        Response::created(
            $mensaje,
            'Mensaje enviado correctamente'
        );
    }
}