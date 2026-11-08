<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LogActividad',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 25),
        new OA\Property(property: 'usuario_id', type: 'integer', nullable: true, example: 4),
        new OA\Property(property: 'accion', type: 'string', example: 'reserva_creada'),
        new OA\Property(property: 'ip_address', type: 'string', nullable: true, example: '192.0.2.10'),
        new OA\Property(property: 'fecha', type: 'string', format: 'date-time', example: '2026-11-03T22:29:18Z'),
        new OA\Property(property: 'usuario_nombre', type: 'string', nullable: true, example: 'María Pérez'),
        new OA\Property(property: 'usuario_email', type: 'string', format: 'email', nullable: true, example: 'maria@example.com'),
    ]
)]
#[OA\Schema(
    schema: 'LogActividadListResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/LogActividad')
        ),
        new OA\Property(property: 'message', type: 'string', example: 'Lista de logs obtenida correctamente'),
    ]
)]
#[OA\Schema(
    schema: 'LogActividadResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', ref: '#/components/schemas/LogActividad'),
    ]
)]
#[OA\Schema(
    schema: 'LogActividadErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'error', type: 'string', example: 'Acceso denegado'),
    ]
)]
#[OA\Schema(
    schema: 'LogActividadValidationErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'error', type: 'string', example: 'Error de validación'),
        new OA\Property(
            property: 'validation_errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
            example: ['id' => ['El ID debe ser mayor a 0']]
        ),
    ]
)]
final class LogActividadSchemas
{
}
