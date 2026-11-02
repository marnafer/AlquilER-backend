<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'NotificacionResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 25
        ),
        new OA\Property(
            property: 'usuario_id',
            type: 'integer',
            example: 12
        ),
        new OA\Property(
            property: 'tipo',
            type: 'string',
            example: 'mensaje_nuevo'
        ),
        new OA\Property(
            property: 'titulo',
            type: 'string',
            example: 'Nuevo mensaje'
        ),
        new OA\Property(
            property: 'mensaje',
            type: 'string',
            example: 'Tienes un nuevo mensaje en una consulta'
        ),
        new OA\Property(
            property: 'referencia_id',
            type: 'integer',
            nullable: true,
            example: 5003
        ),
        new OA\Property(
            property: 'leida',
            type: 'boolean',
            example: false
        ),
        new OA\Property(
            property: 'fecha_notificacion',
            type: 'string',
            format: 'date-time',
            example: '2026-11-01T17:07:01'
        ),
    ]
)]
#[OA\Schema(
    schema: 'NotificacionListResponse',
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
                        ref: '#/components/schemas/NotificacionResponse'
                    )
                ),
                new OA\Property(
                    property: 'no_leidas',
                    type: 'integer',
                    example: 3
                ),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'NotificacionNoLeidasResponse',
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
                    property: 'no_leidas',
                    type: 'integer',
                    example: 3
                ),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'NotificacionMutationResponse',
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
            example: 'Notificación marcada como leída'
        ),
    ]
)]
#[OA\Schema(
    schema: 'NotificacionErrorResponse',
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
            example: 'Notificación no encontrada'
        ),
    ]
)]
final class NotificacionSchemas
{
}
