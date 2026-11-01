<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UsuarioResponse',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 15),
        new OA\Property(property: 'nombre', type: 'string', example: 'Mariano'),
        new OA\Property(property: 'apellido', type: 'string', example: 'Fernández'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'usuario@example.com'),
        new OA\Property(property: 'telefono', type: 'string', example: '3435123456'),
        new OA\Property(property: 'domicilio', type: 'string', example: 'Calle 123'),
        new OA\Property(property: 'rol_id', type: 'integer', example: 1),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true, example: '2026-10-20T12:00:00Z'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'UsuarioListResponse',
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/UsuarioResponse')
        ),
        new OA\Property(property: 'total', type: 'integer', example: 25),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'UsuarioAdminCreateRequest',
    required: ['nombre', 'apellido', 'email', 'telefono', 'domicilio', 'contrasena', 'rol_id'],
    properties: [
        new OA\Property(property: 'nombre', type: 'string', minLength: 2, maxLength: 50, example: 'Mariano'),
        new OA\Property(property: 'apellido', type: 'string', minLength: 2, maxLength: 50, example: 'Fernández'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 100, example: 'usuario@example.com'),
        new OA\Property(property: 'telefono', type: 'string', example: '3435123456'),
        new OA\Property(property: 'domicilio', type: 'string', minLength: 5, maxLength: 100, example: 'Calle 123'),
        new OA\Property(property: 'contrasena', type: 'string', format: 'password', minLength: 6, maxLength: 255, example: 'MiPassword123'),
        new OA\Property(property: 'rol_id', type: 'integer', enum: [1, 2], example: 1),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'UsuarioUpdateRequest',
    properties: [
        new OA\Property(property: 'nombre', type: 'string', minLength: 2, maxLength: 50, example: 'Mariano'),
        new OA\Property(property: 'apellido', type: 'string', minLength: 2, maxLength: 50, example: 'Fernández'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 100, example: 'usuario@example.com'),
        new OA\Property(property: 'telefono', type: 'string', example: '3435123456'),
        new OA\Property(property: 'domicilio', type: 'string', minLength: 5, maxLength: 100, example: 'Calle 123'),
        new OA\Property(property: 'contrasena', type: 'string', format: 'password', minLength: 6, maxLength: 255, example: 'NuevaPassword123'),
        new OA\Property(property: 'contrasena_actual', type: 'string', format: 'password', example: 'MiPassword123'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'UsuarioPublicProfileResponse',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 15),
        new OA\Property(property: 'nombre', type: 'string', example: 'Mariano'),
        new OA\Property(property: 'apellido', type: 'string', example: 'Fernández'),
        new OA\Property(
            property: 'calificaciones',
            properties: [
                new OA\Property(property: 'inquilino', type: 'number', format: 'float', example: 4.5),
                new OA\Property(property: 'propiedades', type: 'number', format: 'float', nullable: true, example: 4.8),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'resenas',
            properties: [
                new OA\Property(
                    property: 'inquilino',
                    type: 'array',
                    items: new OA\Items(type: 'object')
                ),
                new OA\Property(
                    property: 'propiedades',
                    type: 'array',
                    items: new OA\Items(type: 'object')
                ),
            ],
            type: 'object'
        ),
    ],
    type: 'object'
)]
final class UsuarioSchemas
{
}
