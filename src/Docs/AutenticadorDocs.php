<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

final class AutenticadorDocs
{
    #[OA\Post(
        path: '/api/autenticador/login',
        summary: 'Iniciar sesión',
        description: 'Autentica un usuario y devuelve un access token y un refresh token.',
        tags: ['Autenticador'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/LoginRequest'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Inicio de sesión exitoso',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/AuthResponse'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciales inválidas'
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación'
            ),
            new OA\Response(
                response: 400,
                description: 'Cuerpo de la solicitud inválido'
            ),
        ]
    )]
    public function login(): void
    {
    }

    #[OA\Post(
    path: '/api/autenticador/register',
    summary: 'Registrar usuario',
    description: 'Registra un nuevo usuario en el sistema.',
    tags: ['Autenticador'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/RegisterRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Usuario registrado correctamente'
        ),
        new OA\Response(
            response: 422,
            description: 'Error de validación'
        ),
        new OA\Response(
            response: 400,
            description: 'Cuerpo de la solicitud inválido'
        ),
    ]
)]
public function register(): void
{
}

#[OA\Post(
    path: '/api/autenticador/logout',
    summary: 'Cerrar sesión',
    description: 'Invalida el refresh token proporcionado.',
    tags: ['Autenticador'],
    security: [
        ['bearerAuth' => []]
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/RefreshTokenRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Sesión cerrada correctamente'
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado'
        ),
        new OA\Response(
            response: 400,
            description: 'Cuerpo de la solicitud inválido'
        ),
    ]
)]
public function logout(): void
{
}

#[OA\Post(
    path: '/api/autenticador/refresh',
    summary: 'Refrescar token',
    description: 'Genera un nuevo access token y un nuevo refresh token a partir de un refresh token válido.',
    tags: ['Autenticador'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/RefreshTokenRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Token refrescado correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/AuthResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Refresh token inválido o expirado'
        ),
        new OA\Response(
            response: 422,
            description: 'Error de validación'
        ),
        new OA\Response(
            response: 400,
            description: 'Cuerpo de la solicitud inválido'
        ),
    ]
)]
public function refresh(): void
{
}

#[OA\Post(
    path: '/api/autenticador/recuperar',
    summary: 'Solicitar recuperación de contraseña',
    description: 'Envía un enlace de recuperación al correo indicado. La respuesta no revela si el correo existe.',
    tags: ['Autenticador'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/RecuperarPasswordRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Solicitud procesada correctamente'
        ),
        new OA\Response(
            response: 422,
            description: 'Error de validación'
        ),
        new OA\Response(
            response: 400,
            description: 'Cuerpo de la solicitud inválido'
        ),
    ]
)]
public function solicitarRecuperacion(): void
{
}

#[OA\Post(
    path: '/api/autenticador/restablecer',
    summary: 'Restablecer contraseña',
    description: 'Restablece la contraseña utilizando un token de recuperación válido.',
    tags: ['Autenticador'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/RestablecerPasswordRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Contraseña restablecida correctamente'
        ),
        new OA\Response(
            response: 400,
            description: 'Enlace de recuperación inválido, utilizado o expirado'
        ),
        new OA\Response(
            response: 422,
            description: 'Error de validación'
        ),
    ]
)]
public function restablecerContrasena(): void
{
}
}