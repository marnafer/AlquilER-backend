<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Servicio',
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
            maxLength: 50,
            example: 'Piscina'
        ),
    ]
)]

#[OA\Schema(
    schema: 'ServicioListResponse',
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
                        ref: '#/components/schemas/Servicio'
                    )
                ),
                new OA\Property(
                    property: 'total',
                    type: 'integer',
                    example: 3
                ),
            ]
        ),
    ]
)]

#[OA\Schema(
    schema: 'ServicioResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/Servicio'
        ),
    ]
)]

final class ServicioSchemas
{
}