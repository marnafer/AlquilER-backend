<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Notificaciones',
    description: 'Consulta y gestión de las notificaciones del usuario autenticado'
)]
#[OA\Get(
    path: '/api/notificaciones',
    operationId: 'listarNotificaciones',
    summary: 'Listar notificaciones',
    description: 'Obtiene las notificaciones del usuario autenticado y la cantidad de notificaciones no leídas.',
    tags: ['Notificaciones'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Notificaciones obtenidas correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/NotificacionListResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Token ausente, inválido o expirado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/NotificacionErrorResponse'
            )
        ),
    ]
)]
#[OA\Get(
    path: '/api/notificaciones/no-leidas',
    operationId: 'contarNotificacionesNoLeidas',
    summary: 'Contar notificaciones no leídas',
    description: 'Obtiene la cantidad de notificaciones no leídas del usuario autenticado.',
    tags: ['Notificaciones'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Cantidad de notificaciones no leídas obtenida correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/NotificacionNoLeidasResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Token ausente, inválido o expirado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/NotificacionErrorResponse'
            )
        ),
    ]
)]
#[OA\Put(
    path: '/api/notificaciones/{id}/leer',
    operationId: 'marcarNotificacionLeida',
    summary: 'Marcar notificación como leída',
    description: 'Marca como leída una notificación perteneciente al usuario autenticado.',
    tags: ['Notificaciones'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identificador de la notificación.',
            schema: new OA\Schema(
                type: 'integer',
                minimum: 1
            ),
            example: 25
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Notificación marcada como leída',
            content: new OA\JsonContent(
                ref: '#/components/schemas/NotificacionMutationResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Token ausente, inválido o expirado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/NotificacionErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'La notificación pertenece a otro usuario',
            content: new OA\JsonContent(
                ref: '#/components/schemas/NotificacionErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Notificación inexistente o identificador inválido',
            content: new OA\JsonContent(
                ref: '#/components/schemas/NotificacionErrorResponse'
            )
        ),
    ]
)]
#[OA\Put(
    path: '/api/notificaciones/leer-todas',
    operationId: 'marcarTodasNotificacionesLeidas',
    summary: 'Marcar todas las notificaciones como leídas',
    description: 'Marca como leídas todas las notificaciones pendientes del usuario autenticado.',
    tags: ['Notificaciones'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Notificaciones marcadas como leídas',
            content: new OA\JsonContent(
                ref: '#/components/schemas/NotificacionMutationResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Token ausente, inválido o expirado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/NotificacionErrorResponse'
            )
        ),
    ]
)]
final class NotificacionDocs
{
}
