<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\MensajeConsultaService;

class MensajeConsultaController
{
    public function __construct(
        private readonly MensajeConsultaService $service,
        private readonly Authenticator $auth
    ) {
    }

    public function index(Request $request, $consultaId): Response
    {
        $user = $this->auth->verificar($request);

        $resultado = $this->service->obtenerHistorial(
            (int) $consultaId,
            (int) $user->sub,
            (int) $user->rol_id,
            $request->query('antes_de_id'),
            $request->query('despues_de_id'),
            $request->query('limite')
        );

        return Response::success($resultado);
    }

    public function store(Request $request, $consultaId): Response
    {
        $user = $this->auth->verificar($request);

        $data = $request->json();
        $data['consulta_id'] = (int) $consultaId;

        $mensaje = $this->service->crearMensaje(
            $data,
            (int) $user->sub,
            (int) $user->rol_id
        );

        return Response::created(
            $mensaje,
            'Mensaje enviado correctamente'
        );
    }
}
