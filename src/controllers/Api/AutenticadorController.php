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

    public function logout(): void
    {
        AutenticadorMiddleware::verificar();

        Response::success(
            [],
            200,
            'Logout (el cliente elimina el token)'
        );
    }
}