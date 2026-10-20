<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Services\AutenticadorService;

class AutenticadorController
{
    public function __construct(
        private readonly AutenticadorService $service
    ) {
    }

    public function login(Request $request): Response
    {
        return Response::success(
            $this->service->login($request->json())
        );
    }

    public function register(Request $request): Response
    {
        $this->service->registrar($request->json());

        return Response::created(
            [],
            'Usuario registrado'
        );
    }

    /**
     * Refresca el access token usando un refresh token válido
     * POST /api/autenticador/refresh
     * Body: { "refresh_token": "..." }
     */
    public function refresh(Request $request): Response
    {
        $result = $this->service->refresh($request->json());

        return Response::success(
            $result,
            200,
            'Token refrescado correctamente'
        );
    }

    public function logout(Request $request): Response
    {
        $this->service->logout($request->json());

        return Response::success(
            [],
            200,
            'Sesión cerrada correctamente'
        );
    }
}
