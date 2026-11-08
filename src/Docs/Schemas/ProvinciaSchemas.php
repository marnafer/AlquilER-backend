<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProvinciaResponse',
    type: 'object',
    required: ['id', 'nombre'],
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'nombre',
            type: 'string',
            example: 'Entre Ríos'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProvinciaListResponse',
    type: 'object',
    required: ['success', 'data'],
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'data',
            type: 'object',
            required: ['items', 'total'],
            properties: [
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        ref: '#/components/schemas/ProvinciaResponse'
                    )
                ),
                new OA\Property(
                    property: 'total',
                    type: 'integer',
                    example: 24
                ),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProvinciaCreateRequest',
    type: 'object',
    required: ['nombre'],
    properties: [
        new OA\Property(
            property: 'nombre',
            type: 'string',
            minLength: 3,
            maxLength: 100,
            pattern: '^[\p{L}\s]+$',
            example: 'Entre Ríos'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProvinciaUpdateRequest',
    type: 'object',
    minProperties: 1,
    properties: [
        new OA\Property(
            property: 'nombre',
            type: 'string',
            minLength: 3,
            maxLength: 100,
            pattern: '^[\p{L}\s]+$',
            example: 'Entre Ríos'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProvinciaMutationResponse',
    type: 'object',
    required: ['success', 'data', 'message'],
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Provincia actualizada exitosamente'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProvinciaCreatedResponse',
    type: 'object',
    required: ['success', 'data', 'message'],
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/ProvinciaResponse'
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Provincia creada exitosamente'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProvinciaErrorResponse',
    type: 'object',
    required: ['success', 'error'],
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: false
        ),
        new OA\Property(
            property: 'error',
            type: 'string',
            example: 'Provincia no encontrada'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ProvinciaValidationErrorResponse',
    type: 'object',
    required: ['success', 'error', 'validation_errors'],
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: false
        ),
        new OA\Property(
            property: 'error',
            type: 'string',
            example: 'Error de validación'
        ),
        new OA\Property(
            property: 'validation_errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'string'
            ),
            example: [
                'nombre' => 'El nombre debe tener al menos 3 caracteres'
            ]
        ),
    ]
)]
final class ProvinciaSchemas
{
}
