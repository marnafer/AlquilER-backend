<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Rol',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'nombre', type: 'string', example: 'Administrador'),
    ]
)]
#[OA\Schema(
    schema: 'RolListResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Rol')
                ),
                new OA\Property(property: 'total', type: 'integer', example: 2),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'RolResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/Rol'
        ),
    ]
)]
#[OA\Schema(
    schema: 'RolCreateRequest',
    type: 'object',
    required: ['nombre'],
    properties: [
        new OA\Property(
            property: 'nombre',
            type: 'string',
            minLength: 3,
            maxLength: 30,
            pattern: '^[\p{L}\s]+$',
            example: 'Moderador'
        ),
    ]
)]
#[OA\Schema(
    schema: 'RolUpdateRequest',
    type: 'object',
    required: ['nombre'],
    properties: [
        new OA\Property(
            property: 'nombre',
            type: 'string',
            minLength: 3,
            maxLength: 30,
            pattern: '^[\p{L}\s]+$',
            example: 'Moderador'
        ),
    ]
)]
#[OA\Schema(
    schema: 'RolCreatedResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/Rol'
        ),
        new OA\Property(property: 'message', type: 'string', example: 'Rol creado exitosamente'),
    ]
)]
#[OA\Schema(
    schema: 'RolMutationResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(property: 'message', type: 'string', example: 'Rol actualizado exitosamente'),
    ]
)]
#[OA\Schema(
    schema: 'RolErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(property: 'error', type: 'string', example: 'Rol no encontrado'),
    ]
)]
#[OA\Schema(
    schema: 'RolValidationErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        ),
        new OA\Property(property: 'error', type: 'string', example: 'Error de validación'),
    ]
)]
final class RolSchemas
{
}