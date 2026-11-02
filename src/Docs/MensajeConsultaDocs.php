<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Mensajes de Consulta',
    description: 'Envío y consulta del historial de mensajes'
)]
#[OA\Get(
    path: '/api/consultas/{consulta_id}/mensajes',
    operationId: 'obtenerHistorialMensajesConsulta',
    summary: 'Obtener historial de mensajes de una consulta',
    description: 'Devuelve el historial paginado de mensajes. Solo pueden acceder el interesado, el propietario de la propiedad o un administrador.',
    tags: ['Mensajes de Consulta'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'consulta_id',
            in: 'path',
            required: true,
            description: 'ID de la consulta',
            schema: new OA\Schema(type: 'integer', minimum: 1),
            example: 5003
        ),
        new OA\Parameter(
            name: 'antes_de_id',
            in: 'query',
            description: 'Obtiene mensajes anteriores al ID indicado. No se puede combinar con despues_de_id.',
            schema: new OA\Schema(type: 'integer', minimum: 1),
            example: 10001
        ),
        new OA\Parameter(
            name: 'despues_de_id',
            in: 'query',
            description: 'Obtiene mensajes posteriores al ID indicado. No se puede combinar con antes_de_id.',
            schema: new OA\Schema(type: 'integer', minimum: 1),
            example: 10001
        ),
        new OA\Parameter(
            name: 'limite',
            in: 'query',
            description: 'Cantidad máxima de mensajes. Predeterminado: 10.',
            schema: new OA\Schema(
                type: 'integer',
                minimum: 1,
                maximum: 50,
                default: 10
            ),
            example: 10
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Historial obtenido correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/MensajeConsultaHistorialResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Token ausente, inválido o expirado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/MensajeConsultaErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'El usuario no tiene permiso para acceder a la consulta',
            content: new OA\JsonContent(
                ref: '#/components/schemas/MensajeConsultaErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Consulta inexistente o eliminada para un usuario no administrador',
            content: new OA\JsonContent(
                ref: '#/components/schemas/MensajeConsultaErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'ID o parámetros de paginación inválidos',
            content: new OA\JsonContent(
                ref: '#/components/schemas/MensajeConsultaValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Post(
    path: '/api/consultas/{consulta_id}/mensajes',
    operationId: 'crearMensajeConsulta',
    summary: 'Enviar un mensaje en una consulta',
    description: 'Registra un mensaje y notifica al otro participante. Solo pueden enviar mensajes los participantes autorizados o un administrador.',
    tags: ['Mensajes de Consulta'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'consulta_id',
            in: 'path',
            required: true,
            description: 'ID de la consulta',
            schema: new OA\Schema(type: 'integer', minimum: 1),
            example: 5003
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/MensajeConsultaCreateRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Mensaje enviado correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/MensajeConsultaCreatedResponse'
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Cuerpo de solicitud ausente o inválido',
            content: new OA\JsonContent(
                ref: '#/components/schemas/MensajeConsultaErrorResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Token ausente, inválido o expirado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/MensajeConsultaErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'El usuario no tiene permiso para enviar mensajes en esta consulta',
            content: new OA\JsonContent(
                ref: '#/components/schemas/MensajeConsultaErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Consulta inexistente o eliminada para un usuario no administrador',
            content: new OA\JsonContent(
                ref: '#/components/schemas/MensajeConsultaErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Datos del mensaje o ID de consulta inválidos',
            content: new OA\JsonContent(
                ref: '#/components/schemas/MensajeConsultaValidationErrorResponse'
            )
        ),
    ]
)]
final class MensajeConsultaDocs
{
}
