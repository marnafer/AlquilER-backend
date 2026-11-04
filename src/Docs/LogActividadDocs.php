<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/logs-actividad',
    operationId: 'listarLogsActividad',
    summary: 'Listar logs de actividad',
    description: 'Obtiene los registros de actividad ordenados desde el más reciente. Solo accesible para administradores.',
    tags: ['Logs de actividad'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Logs de actividad obtenidos correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/LogActividadListResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/LogActividadErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'La operación está restringida a administradores.',
            content: new OA\JsonContent(ref: '#/components/schemas/LogActividadErrorResponse')
        ),
    ]
)]
#[OA\Get(
    path: '/api/logs-actividad/{id}',
    operationId: 'obtenerLogActividad',
    summary: 'Obtener un log de actividad',
    description: 'Obtiene un registro de actividad por su identificador. Solo accesible para administradores.',
    tags: ['Logs de actividad'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identificador positivo del registro de actividad.',
            schema: new OA\Schema(type: 'integer', minimum: 1),
            example: 25
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Log de actividad obtenido correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/LogActividadResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/LogActividadErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'La operación está restringida a administradores.',
            content: new OA\JsonContent(ref: '#/components/schemas/LogActividadErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'No existe un log con ese identificador.',
            content: new OA\JsonContent(ref: '#/components/schemas/LogActividadErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'El identificador debe ser un entero positivo.',
            content: new OA\JsonContent(ref: '#/components/schemas/LogActividadValidationErrorResponse')
        ),
    ]
)]
final class LogActividadDocs
{
}
