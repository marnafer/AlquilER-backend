<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/servicios',
    operationId: 'listarServicios',
    summary: 'Listar servicios',
    description: 'Obtiene los servicios disponibles. Por defecto, solo devuelve servicios activos. Los parámetros de papelera requieren autenticación y permisos de administrador.',
    tags: ['Servicios'],
    parameters: [
        new OA\Parameter(
            name: 'solo_eliminados',
            in: 'query',
            required: false,
            description: 'Si es true, devuelve únicamente los servicios eliminados. Requiere permisos de administrador.',
            schema: new OA\Schema(
                type: 'boolean',
                default: false
            ),
            example: true
        ),
        new OA\Parameter(
            name: 'incluir_eliminados',
            in: 'query',
            required: false,
            description: 'Si es true, incluye los servicios eliminados junto con los activos. Requiere permisos de administrador.',
            schema: new OA\Schema(
                type: 'boolean',
                default: false
            ),
            example: true
        ),
    ],
    security: [
        [],
        ['bearerAuth' => []],
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Listado de servicios obtenido exitosamente.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ServicioListResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Se requiere autenticación para consultar la papelera.'
        ),
        new OA\Response(
            response: 403,
            description: 'El usuario autenticado no tiene permisos de administrador.'
        ),
    ]
)]

#[OA\Get(
    path: '/api/servicios/{id}',
    operationId: 'obtenerServicio',
    summary: 'Obtener un servicio',
    description: 'Obtiene un servicio activo por su identificador. No requiere autenticación.',
    tags: ['Servicios'],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identificador del servicio.',
            schema: new OA\Schema(
                type: 'integer',
                minimum: 1
            ),
            example: 1
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Servicio obtenido exitosamente.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ServicioResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Servicio no encontrado.'
        ),
        new OA\Response(
            response: 422,
            description: 'El identificador del servicio es inválido.'
        ),
    ]
)]

#[OA\Post(
    path: '/api/servicios',
    operationId: 'crearServicio',
    summary: 'Crear un servicio',
    description: 'Crea un nuevo servicio. Requiere autenticación y permisos de administrador.',
    tags: ['Servicios'],
    security: [
        ['bearerAuth' => []],
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['nombre'],
            properties: [
                new OA\Property(
                    property: 'nombre',
                    type: 'string',
                    minLength: 3,
                    maxLength: 50,
                    example: 'Piscina'
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Servicio creado exitosamente.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ServicioResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.'
        ),
        new OA\Response(
            response: 403,
            description: 'Se requieren permisos de administrador.'
        ),
        new OA\Response(
            response: 409,
            description: 'Ya existe un servicio con ese nombre.'
        ),
        new OA\Response(
            response: 422,
            description: 'Los datos enviados no superan la validación.'
        ),
    ]
)]

#[OA\Put(
    path: '/api/servicios/{id}',
    operationId: 'actualizarServicio',
    summary: 'Actualizar un servicio',
    description: 'Actualiza el nombre de un servicio activo. Requiere autenticación y permisos de administrador.',
    tags: ['Servicios'],
    security: [
        ['bearerAuth' => []],
    ],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identificador del servicio.',
            schema: new OA\Schema(
                type: 'integer',
                minimum: 1
            ),
            example: 1
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'nombre',
                    type: 'string',
                    minLength: 3,
                    maxLength: 50,
                    example: 'Piscina climatizada'
                ),
            ],
            example: [
                'nombre' => 'Piscina climatizada',
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Servicio actualizado exitosamente.'
        ),
        new OA\Response(
            response: 400,
            description: 'No se enviaron campos actualizables.'
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.'
        ),
        new OA\Response(
            response: 403,
            description: 'Se requieren permisos de administrador.'
        ),
        new OA\Response(
            response: 404,
            description: 'Servicio no encontrado.'
        ),
        new OA\Response(
            response: 409,
            description: 'Ya existe otro servicio con ese nombre.'
        ),
        new OA\Response(
            response: 422,
            description: 'Los datos enviados no superan la validación.'
        ),
    ]
)]

#[OA\Delete(
    path: '/api/servicios/{id}',
    operationId: 'eliminarServicio',
    summary: 'Eliminar un servicio',
    description: 'Elimina lógicamente un servicio. No permite eliminar servicios asociados a propiedades. Requiere autenticación y permisos de administrador.',
    tags: ['Servicios'],
    security: [
        ['bearerAuth' => []],
    ],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identificador del servicio.',
            schema: new OA\Schema(
                type: 'integer',
                minimum: 1
            ),
            example: 1
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Servicio eliminado exitosamente.'
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.'
        ),
        new OA\Response(
            response: 403,
            description: 'Se requieren permisos de administrador.'
        ),
        new OA\Response(
            response: 404,
            description: 'Servicio no encontrado.'
        ),
        new OA\Response(
            response: 409,
            description: 'No se puede eliminar el servicio porque tiene propiedades asociadas.'
        ),
        new OA\Response(
            response: 422,
            description: 'El identificador del servicio es inválido.'
        ),
    ]
)]

#[OA\Post(
    path: '/api/servicios/{id}/restaurar',
    operationId: 'restaurarServicio',
    summary: 'Restaurar un servicio',
    description: 'Restaura un servicio eliminado. No permite restaurarlo si ya existe un servicio activo con el mismo nombre. Requiere autenticación y permisos de administrador.',
    tags: ['Servicios'],
    security: [
        ['bearerAuth' => []],
    ],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identificador del servicio eliminado.',
            schema: new OA\Schema(
                type: 'integer',
                minimum: 1
            ),
            example: 1
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Servicio restaurado exitosamente.'
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.'
        ),
        new OA\Response(
            response: 403,
            description: 'Se requieren permisos de administrador.'
        ),
        new OA\Response(
            response: 404,
            description: 'Servicio eliminado no encontrado.'
        ),
        new OA\Response(
            response: 409,
            description: 'Ya existe un servicio activo con ese nombre.'
        ),
        new OA\Response(
            response: 422,
            description: 'El identificador del servicio es inválido.'
        ),
    ]
)]

final class ServicioDocs
{
}