<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'contrasena'],
    properties: [
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'usuario@example.com'
        ),
        new OA\Property(
            property: 'contrasena',
            type: 'string',
            format: 'password',
            example: 'MiPassword123'
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'RegisterRequest',
    required: [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'domicilio',
        'contrasena'
    ],
    properties: [
        new OA\Property(
            property: 'nombre',
            type: 'string',
            example: 'Mariano'
        ),
        new OA\Property(
            property: 'apellido',
            type: 'string',
            example: 'Fernández'
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'mariano@example.com'
        ),
        new OA\Property(
            property: 'telefono',
            type: 'string',
            example: '3435123456'
        ),
        new OA\Property(
            property: 'domicilio',
            type: 'string',
            example: 'Calle 123'
        ),
        new OA\Property(
            property: 'contrasena',
            type: 'string',
            format: 'password',
            example: 'MiPassword123'
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'RefreshTokenRequest',
    required: ['refresh_token'],
    properties: [
        new OA\Property(
            property: 'refresh_token',
            type: 'string',
            example: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'AuthResponse',
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'data',
            properties: [
                new OA\Property(
                    property: 'access_token',
                    type: 'string',
                    example: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'
                ),
                new OA\Property(
                    property: 'refresh_token',
                    type: 'string',
                    example: '550e8400-e29b-41d4-a716-446655440000'
                ),
                new OA\Property(
                    property: 'rol_id',
                    type: 'integer',
                    example: 1
                ),
            ],
            type: 'object'
        ),
    ],
    type: 'object'
    )]
final class AutenticadorSchemas
{
}