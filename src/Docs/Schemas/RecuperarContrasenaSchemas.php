<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RecuperarContrasenaSolicitudRequest',
    type: 'object',
    required: ['email'],
    properties: [
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            maxLength: 255,
            example: 'usuario@correo.com'
        ),
    ]
)]
#[OA\Schema(
    schema: 'RecuperarContrasenaRestablecerRequest',
    type: 'object',
    required: ['email', 'token', 'contrasena'],
    properties: [
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            maxLength: 255,
            example: 'usuario@correo.com'
        ),
        new OA\Property(
            property: 'token',
            type: 'string',
            minLength: 64,
            maxLength: 64,
            pattern: '^[a-f0-9]{64}$',
            example: 'a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef1234'
        ),
        new OA\Property(
            property: 'contrasena',
            type: 'string',
            format: 'password',
            minLength: 8,
            example: 'NuevaClave123!'
        ),
    ]
)]
#[OA\Schema(
    schema: 'RecuperarContrasenaConfirmarCambioRequest',
    type: 'object',
    required: ['email', 'token'],
    properties: [
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            maxLength: 255,
            example: 'usuario@correo.com'
        ),
        new OA\Property(
            property: 'token',
            type: 'string',
            minLength: 64,
            maxLength: 64,
            pattern: '^[a-f0-9]{64}$',
            example: 'a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef1234'
        ),
    ]
)]
#[OA\Schema(
    schema: 'RecuperarContrasenaSuccessResponse',
    type: 'object',
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
            example: 'Operación realizada correctamente'
        ),
    ]
)]
#[OA\Schema(
    schema: 'RecuperarContrasenaErrorResponse',
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
            example: 'El enlace de recuperación no es válido o ha vencido'
        ),
    ]
)]
#[OA\Schema(
    schema: 'RecuperarContrasenaValidationErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: false
        ),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: [
                'email' => 'El correo electrónico no es válido',
            ]
        ),
    ]
)]
final class RecuperarContrasenaSchemas
{
}
