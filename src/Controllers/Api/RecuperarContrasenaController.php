<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Services\RecuperarContrasenaService;

class RecuperarContrasenaController
{
    public function __construct(
        private readonly RecuperarContrasenaService $service
    ) {
    }

    /**
     * POST /api/autenticador/recuperar
     */
    public function solicitar(Request $request): Response
    {
        $this->service->solicitar(
            $request->json()
        );

        return Response::success(
            [],
            200,
            'Si el correo existe, recibirás un enlace para restablecer tu contraseña'
        );
    }

    /**
     * POST /api/autenticador/restablecer
     */
    public function restablecer(Request $request): Response
    {
        $this->service->restablecer(
            $request->json()
        );

        return Response::success(
            [],
            200,
            'Contraseña restablecida correctamente'
        );
    }

    /**
     * POST /api/autenticador/confirmar-cambio
     */
    public function confirmarCambio(Request $request): Response
    {
        $data = $request->json();

        $this->service->confirmarCambio(
            (string) ($data['email'] ?? ''),
            (string) ($data['token'] ?? '')
        );

        return Response::success(
            [],
            200,
            'Contraseña actualizada correctamente'
        );
    }
}
