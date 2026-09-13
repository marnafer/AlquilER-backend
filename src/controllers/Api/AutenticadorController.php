<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\Response;
use App\Helpers\Request;
use App\Middlewares\AutenticadorMiddleware;
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

    public function logout($request = null): void
    {
        AutenticadorMiddleware::verificar();

        Response::success(
            [],
            200,
            'Logout (el cliente elimina el token)'
        );
    }

    /**
     * Refresca el access token
     * POST /api/auth/refresh
     * Body: { "refresh_token": "..." }
     */
    public function refresh(): void
    {
        $data = Request::json();

        try {
            $refreshToken = $data['refresh_token'] ?? null;

            if (!$refreshToken) {
                throw new \Exception('Refresh token requerido', 400);
            }

            $result = $this->service->refresh($refreshToken);

            Response::success($result, 200, 'Token refrescado correctamente');
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            
            if ($statusCode === 400) {
                Response::badRequest($e->getMessage());
            } elseif ($statusCode === 401) {
                Response::unauthorized($e->getMessage());
            } else {
                Response::json(['success' => false, 'error' => $e->getMessage()], $statusCode);
            }
        }
    }
}