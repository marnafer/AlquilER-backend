<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MensajeConsultaCreateRequest',
    type: 'object',
    required: ['mensaje'],
    properties: [
        new OA\Property(
            property: 'mensaje',
            type: 'string',
            minLength: 1,
            maxLength: 2000,
            example: 'Hola, ¿sigue disponible la propiedad?'
        ),
    ]
)]
#[OA\Schema(
    schema: 'MensajeConsultaResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10001),
        new OA\Property(property: 'consulta_id', type: 'integer', example: 5003),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 12),
        new OA\Property(
            property: 'mensaje',
            type: 'string',
            example: 'Probando mensajes'
        ),
        new OA\Property(
            property: 'fecha_mensaje',
            type: 'string',
            format: 'date-time',
            example: '2026-11-01 17:07:01'
        ),
    ]
)]
#[OA\Schema(
    schema: 'MensajeConsultaUsuarioResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'nombre', type: 'string', example: 'Juan'),
        new OA\Property(property: 'apellido', type: 'string', example: 'Pérez'),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'juan@example.com'
        ),
        new OA\Property(
            property: 'telefono',
            type: 'string',
            nullable: true,
            example: '3435000000'
        ),
    ]
)]
#[OA\Schema(
    schema: 'MensajeConsultaHistorialItem',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/MensajeConsultaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'usuario',
                    ref: '#/components/schemas/MensajeConsultaUsuarioResponse'
                ),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'MensajeConsultaHistorialResponse',
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
                        ref: '#/components/schemas/MensajeConsultaHistorialItem'
                    )
                ),
                new OA\Property(property: 'total', type: 'integer', example: 10),
                new OA\Property(
                    property: 'hay_anteriores',
                    type: 'boolean',
                    example: true
                ),
                new OA\Property(
                    property: 'hay_mas_nuevos',
                    type: 'boolean',
                    example: false
                ),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'MensajeConsultaCreatedResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/MensajeConsultaResponse'
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Mensaje enviado correctamente'
        ),
    ]
)]
#[OA\Schema(
    schema: 'MensajeConsultaErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'error',
            type: 'string',
            example: 'No tienes permiso para acceder a esta consulta'
        ),
    ]
)]
#[OA\Schema(
    schema: 'MensajeConsultaValidationErrorResponse',
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
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: [
                'limite' => 'El límite debe ser un entero entre 1 y 50',
            ]
        ),
    ]
)]
final class MensajeConsultaSchemas
{
}
