<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LocalidadCreateRequest',
    type: 'object',
    required: ['nombre', 'codigo_postal', 'provincia_id'],
    properties: [
        new OA\Property(
            property: 'nombre',
            type: 'string',
            minLength: 2,
            maxLength: 100,
            example: 'Crespo'
        ),
        new OA\Property(
            property: 'codigo_postal',
            type: 'string',
            minLength: 2,
            maxLength: 15,
            example: '3116'
        ),
        new OA\Property(
            property: 'provincia_id',
            type: 'integer',
            minimum: 1,
            example: 8
        ),
    ]
)]
#[OA\Schema(
    schema: 'LocalidadUpdateRequest',
    type: 'object',
    description: 'Actualización parcial. Debe enviarse al menos un campo modificable.',
    minProperties: 1,
    properties: [
        new OA\Property(
            property: 'nombre',
            type: 'string',
            minLength: 2,
            maxLength: 100,
            example: 'Crespo'
        ),
        new OA\Property(
            property: 'codigo_postal',
            type: 'string',
            minLength: 2,
            maxLength: 15,
            example: '3116'
        ),
        new OA\Property(
            property: 'provincia_id',
            type: 'integer',
            minimum: 1,
            example: 8
        ),
    ]
)]
#[OA\Schema(
    schema: 'LocalidadResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'nombre',
            type: 'string',
            example: 'Crespo'
        ),
        new OA\Property(
            property: 'codigo_postal',
            type: 'string',
            example: '3116'
        ),
        new OA\Property(
            property: 'provincia_id',
            type: 'integer',
            example: 8
        ),
    ]
)]
#[OA\Schema(
    schema: 'LocalidadListResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        ref: '#/components/schemas/LocalidadResponse'
                    )
                ),
                new OA\Property(
                    property: 'total',
                    type: 'integer',
                    example: 12
                ),
            ]
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            nullable: true,
            example: null
        ),
    ]
)]
#[OA\Schema(
    schema: 'LocalidadSingleResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/LocalidadResponse'
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            nullable: true,
            example: null
        ),
    ]
)]
#[OA\Schema(
    schema: 'LocalidadMutationResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'data',
            type: 'object',
            example: []
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Localidad actualizada exitosamente'
        ),
    ]
)]
#[OA\Schema(
    schema: 'LocalidadErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: false
        ),
        new OA\Property(
            property: 'error',
            type: 'string',
            example: 'Localidad no encontrada'
        ),
    ]
)]
#[OA\Schema(
    schema: 'LocalidadValidationErrorResponse',
    type: 'object',
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
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
            example: [
                'nombre' => [
                    'El nombre no puede exceder los 100 caracteres'
                ]
            ]
        ),
    ]
)]
final class LocalidadSchemas
{
}
