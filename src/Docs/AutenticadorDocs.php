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

#[OA\Get(
    path: '/api/autenticador/verificar-email',
    operationId: 'verificarEmail',
    summary: 'Verificar dirección de correo electrónico',
    description: 'Verifica el correo electrónico usando el email y el token recibidos en el enlace enviado al usuario. El enlace debe estar vigente y solo puede utilizarse una vez.',
    tags: ['Autenticador'],
    parameters: [
        new OA\Parameter(
            name: 'email',
            in: 'query',
            required: true,
            description: 'Dirección de correo electrónico asociada a la cuenta.',
            schema: new OA\Schema(type: 'string', format: 'email'),
            example: 'usuario@example.com'
        ),
        new OA\Parameter(
            name: 'token',
            in: 'query',
            required: true,
            description: 'Token de verificación incluido en el enlace del correo.',
            schema: new OA\Schema(type: 'string'),
            example: 'token-de-verificacion'
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Correo verificado correctamente.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'), example: []),
                    new OA\Property(property: 'message', type: 'string', example: 'Correo verificado correctamente'),
                ],
                type: 'object'
            )
        ),
        new OA\Response(
            response: 400,
            description: 'El enlace de verificación es inválido o expiró.'
        ),
        new OA\Response(
            response: 422,
            description: 'Falta el email o el token de verificación.'
        ),
    ]
)]
public function verificarEmail(): void
{
}
}