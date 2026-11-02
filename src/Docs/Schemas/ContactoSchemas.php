<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ContactoRequest',
    type: 'object',
    required: ['nombre', 'email', 'asunto', 'mensaje'],
    properties: [
        new OA\Property(
            property: 'nombre',
            type: 'string',
            maxLength: 100,
            example: 'Mariano Fernández'
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'mariano@example.com'
        ),
        new OA\Property(
            property: 'asunto',
            type: 'string',
            minLength: 3,
            maxLength: 150,
            example: 'Consulta sobre una propiedad'
        ),
        new OA\Property(
            property: 'mensaje',
            type: 'string',
            minLength: 10,
            maxLength: 5000,
            example: 'Quisiera recibir más información sobre el servicio.'
        ),
    ]
)]

#[OA\Schema(
    schema: 'ContactoSuccessResponse',
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
            items: new OA\Items(type: 'string'),
            example: []
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Mensaje enviado correctamente'
        ),
    ]
)]

#[OA\Schema(
    schema: 'ContactoErrorResponse',
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
            example: 'El servicio de contacto no está configurado'
        ),
    ]
)]

#[OA\Schema(
    schema: 'ContactoValidationErrorResponse',
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
                'nombre' => 'El nombre es requerido',
                'email' => 'El email no es válido',
                'asunto' => 'El asunto debe tener al menos 3 caracteres',
                'mensaje' => 'El mensaje debe tener al menos 10 caracteres',
            ]
        ),
    ]
)]

final class ContactoSchemas
{
}
