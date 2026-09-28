<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Request;
use App\Helpers\Response;
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
    public function solicitar(): void
    {
        $this->service->solicitar(
            Request::json()
        );

        Response::success(
            [],
            200,
            'Si el correo existe, recibirás un enlace para restablecer tu contraseña'
        );
    }

    /**
     * POST /api/autenticador/restablecer
     */
    public function restablecer(): void
    {
        $this->service->restablecer(
            Request::json()
        );

        Response::success(
            [],
            200,
            'Contraseña restablecida correctamente'
        );
    }
}