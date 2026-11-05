<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\AuthCookies;
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
        $tokens = $this->service->login($request->json());

        return AuthCookies::attach(
            Response::success(['rol_id' => $tokens['rol_id']]),
            $tokens
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
     * Refresca los tokens usando la cookie HttpOnly del refresh token.
     * Acepta el body JSON anterior para mantener compatibilidad con clientes existentes.
     */
    public function refresh(Request $request): Response
    {
        $result = $this->service->refresh([
            'refresh_token' => $this->refreshToken($request),
        ]);

        return AuthCookies::attach(
            Response::success(
                ['rol_id' => $result['rol_id']],
                200,
                'Token refrescado correctamente'
            ),
            $result
        );
    }

    public function logout(Request $request): Response
    {
        $refreshToken = $request->cookie(AuthCookies::REFRESH_TOKEN);

        if (
            $refreshToken === null
            && $request->rawBody() !== null
            && trim($request->rawBody()) !== ''
        ) {
            $refreshToken = $request->json()['refresh_token'] ?? null;
        }

        $this->service->logout(['refresh_token' => $refreshToken]);

        return AuthCookies::clear(
            Response::success(
                [],
                200,
                'Sesión cerrada correctamente'
            )
        );
    }

    private function refreshToken(Request $request): ?string
    {
        $refreshToken = $request->cookie(AuthCookies::REFRESH_TOKEN);

        if ($refreshToken !== null) {
            return $refreshToken;
        }

        if ($request->rawBody() === null || trim($request->rawBody()) === '') {
            return null;
        }

        $token = $request->json()['refresh_token'] ?? null;

        return is_string($token) ? $token : null;
    }

    public function verificar(Request $request): Response
    {
        $this->service->verificarEmail($request->queryParams());

        return Response::success(
            [],
            200,
            'Correo verificado correctamente'
        );
    }
}
