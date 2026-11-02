<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PropiedadImagenCreateRequest',
    type: 'object',
    required: ['propiedad_id', 'imagen'],
    properties: [
        new OA\Property(
            property: 'propiedad_id',
            type: 'integer',
            example: 10013
        ),
        new OA\Property(
            property: 'descripcion',
            type: 'string',
            nullable: true,
            maxLength: 300,
            example: 'Frente de la propiedad'
        ),
        new OA\Property(
            property: 'imagen',
            type: 'string',
            format: 'binary'
        )
    ]
)]

#[OA\Schema(
    schema: 'PropiedadImagenPropiedad',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10013),
        new OA\Property(property: 'titulo', type: 'string', example: 'Casa con patio'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Casa con quincho'),
        new OA\Property(property: 'precio', type: 'number', example: 500000),
        new OA\Property(property: 'expensas', type: 'number', nullable: true, example: 65000),
        new OA\Property(property: 'direccion', type: 'string', example: 'Los Álamos 670'),
        new OA\Property(property: 'cantidad_ambientes', type: 'integer', example: 3),
        new OA\Property(property: 'cantidad_dormitorios', type: 'integer', example: 2),
        new OA\Property(property: 'cantidad_banos', type: 'integer', example: 1),
        new OA\Property(property: 'capacidad', type: 'integer', example: 5),
        new OA\Property(property: 'acepta_mascotas', type: 'boolean', example: false),
        new OA\Property(property: 'acepta_hijos', type: 'boolean', example: false),
        new OA\Property(
            property: 'requisitos_interesados',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: []
        ),
        new OA\Property(property: 'disponible', type: 'boolean', example: true),
        new OA\Property(property: 'destacada', type: 'boolean', example: false),
        new OA\Property(property: 'categoria_id', type: 'integer', example: 1),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 12),
        new OA\Property(property: 'localidad_id', type: 'integer', example: 1),
        new OA\Property(
            property: 'fecha_publicacion',
            type: 'string',
            format: 'date-time',
            example: '2026-10-30T01:36:23.000000Z'
        ),
        new OA\Property(
            property: 'imagen_url',
            type: 'string',
            nullable: true,
            example: '/uploads/propiedades/bc3275926e41beea6d9bc904e4c68865.jpg'
        ),
        new OA\Property(
            property: 'imagen_principal',
            ref: '#/components/schemas/PropiedadImagenBase',
            nullable: true
        )
    ]
)]

#[OA\Schema(
    schema: 'PropiedadImagenBase',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'propiedad_id', type: 'integer', example: 10013),
        new OA\Property(
            property: 'ruta',
            type: 'string',
            example: '/uploads/propiedades/bc3275926e41beea6d9bc904e4c68865.jpg'
        ),
        new OA\Property(
            property: 'descripcion',
            type: 'string',
            nullable: true,
            example: null
        ),
        new OA\Property(
            property: 'es_principal',
            type: 'integer',
            enum: [0, 1],
            example: 1
        )
    ]
)]

#[OA\Schema(
    schema: 'PropiedadImagenResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PropiedadImagenBase'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'propiedad',
                    ref: '#/components/schemas/PropiedadImagenPropiedad'
                )
            ]
        )
    ]
)]

#[OA\Schema(
    schema: 'PropiedadImagenListResponse',
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
                    items: new OA\Items(
                        ref: '#/components/schemas/PropiedadImagenResponse'
                    )
                ),
                new OA\Property(property: 'total', type: 'integer', example: 10)
            ]
        )
    ]
)]

#[OA\Schema(
    schema: 'PropiedadImagenSingleResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/PropiedadImagenResponse'
        )
    ]
)]

#[OA\Schema(
    schema: 'PropiedadImagenCreatedResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/PropiedadImagenResponse'
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Imagen guardada correctamente'
        )
    ]
)]

#[OA\Schema(
    schema: 'PropiedadImagenMutationResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'object',
            example: []
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Imagen eliminada correctamente'
        )
    ]
)]

#[OA\Schema(
    schema: 'PropiedadImagenErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'error',
            type: 'string',
            example: 'Imagen no encontrada'
        )
    ]
)]

#[OA\Schema(
    schema: 'PropiedadImagenValidationErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
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
                'imagen' => 'Debe enviar una imagen'
            ]
        )
    ]
)]

final class PropiedadImagenSchemas
{
}
