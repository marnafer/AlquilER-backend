<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FavoritoCreateRequest',
    type: 'object',
    required: ['propiedad_id'],
    properties: [
        new OA\Property(
            property: 'propiedad_id',
            type: 'integer',
            minimum: 1,
            example: 15
        ),
    ]
)]

#[OA\Schema(
    schema: 'FavoritoPropiedadDetalle',
    allOf: [
        new OA\Schema(
            ref: '#/components/schemas/PropiedadResponse'
        ),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'categoria',
                    ref: '#/components/schemas/PropiedadCategoria'
                ),
                new OA\Property(
                    property: 'localidad',
                    ref: '#/components/schemas/PropiedadLocalidad'
                ),
                new OA\Property(
                    property: 'imagenes',
                    type: 'array',
                    items: new OA\Items(
                        ref: '#/components/schemas/PropiedadImagen'
                    )
                ),
            ]
        ),
    ]
)]

#[OA\Schema(
    schema: 'FavoritoItem',
    type: 'object',
    required: [
        'id',
        'usuario_id',
        'propiedad_id',
        'propiedad'
    ],
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 42
        ),
        new OA\Property(
            property: 'usuario_id',
            type: 'integer',
            example: 8
        ),
        new OA\Property(
            property: 'propiedad_id',
            type: 'integer',
            example: 15
        ),
        new OA\Property(
            property: 'propiedad',
            ref: '#/components/schemas/FavoritoPropiedadDetalle'
        ),
    ]
)]

#[OA\Schema(
    schema: 'FavoritoCreateData',
    type: 'object',
    required: ['propiedad_id', 'es_favorito'],
    properties: [
        new OA\Property(
            property: 'propiedad_id',
            type: 'integer',
            example: 15
        ),
        new OA\Property(
            property: 'es_favorito',
            type: 'boolean',
            example: true
        ),
    ]
)]

#[OA\Schema(
    schema: 'FavoritoListResponse',
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
            items: new OA\Items(
                ref: '#/components/schemas/FavoritoItem'
            )
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Favoritos obtenidos correctamente'
        ),
    ]
)]

#[OA\Schema(
    schema: 'FavoritoCreateResponse',
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
            ref: '#/components/schemas/FavoritoCreateData'
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Propiedad agregada a favoritos'
        ),
    ]
)]

#[OA\Schema(
    schema: 'FavoritoDeleteResponse',
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
            type: 'object',
            nullable: true,
            example: null
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Propiedad eliminada de favoritos'
        ),
    ]
)]

#[OA\Schema(
    schema: 'FavoritoErrorResponse',
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
            example: 'La propiedad no existe'
        ),
    ]
)]

#[OA\Schema(
    schema: 'FavoritoValidationErrorResponse',
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
            example: [
                'propiedad_id' => 'El ID de propiedad debe ser un entero positivo'
            ]
        ),
    ]
)]

final class FavoritoSchemas
{
}
