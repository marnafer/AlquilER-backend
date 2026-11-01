<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CategoriaResponse',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 3
        ),
        new OA\Property(
            property: 'nombre',
            type: 'string',
            minLength: 3,
            maxLength: 50,
            example: 'Departamento'
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CategoriaCollectionResponse',
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(
                ref: '#/components/schemas/CategoriaResponse'
            )
        ),
        new OA\Property(
            property: 'total',
            type: 'integer',
            example: 5
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CategoriaCreateRequest',
    required: ['nombre'],
    properties: [
        new OA\Property(
            property: 'nombre',
            type: 'string',
            minLength: 3,
            maxLength: 50,
            example: 'Departamento'
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CategoriaUpdateRequest',
    required: ['nombre'],
    properties: [
        new OA\Property(
            property: 'nombre',
            type: 'string',
            minLength: 3,
            maxLength: 50,
            example: 'Departamento'
        ),
    ],
    type: 'object'
)]
final class CategoriaSchemas
{
}
