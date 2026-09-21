<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Services\AutenticadorService;

class AutenticadorController
{
    private ?AutenticadorService $service = null;

    public function __construct(AutenticadorService $service)
    {
        $this->service = $service;
    }

    public function login(): void
    {
        $data = Request::json();

        Response::success(
            $this->service->login($data)
        );
    }

    public function register(): void
    {
        $data = Request::json();

        $this->service->registrar($data);

        Response::created(
            [],
            'Usuario registrado'
        );
    }

    /**
     * Alias para register() para compatibilidad con tests
     */
    public function registrar(): void
    {
        $this->register();
    }

    /**
     * Refresca el access token usando un refresh token válido
     * POST /api/autenticador/refresh
     * Body: { "refresh_token": "..." }
     */
    public function refresh(): void
    {
        $data = Request::json();

        $result = $this->service->refresh($data);

        Response::success(
            $result,
            200,
            'Token refrescado correctamente'
        );
    }

    public function logout(): void
    {
        $data = Request::json();

        $this->service->logout($data);

        Response::success(
            [],
            200,
            'Sesión cerrada correctamente'
        );
    }
}