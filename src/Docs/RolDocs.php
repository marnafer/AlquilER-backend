<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/roles',
    summary: 'Listar roles',
    description: 'Obtiene los roles ordenados por ID descendente. La consulta es pública normalmente. Si se solicita la papelera mediante solo_eliminados o incluir_eliminados, se requiere autenticación de administrador.',
    tags: ['Roles'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'solo_eliminados',
            in: 'query',
            description: 'Si es true, devuelve únicamente los roles eliminados. Requiere administrador.',
            required: false,
            schema: new OA\Schema(type: 'boolean', default: false)
        ),
        new OA\Parameter(
            name: 'incluir_eliminados',
            in: 'query',
            description: 'Si es true, incluye también los roles eliminados. Requiere administrador.',
            required: false,
            schema: new OA\Schema(type: 'boolean', default: false)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Roles obtenidos correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolListResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'Se requiere autenticación para consultar la papelera.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'Se requiere rol administrador para consultar la papelera.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
    ]
)]
#[OA\Get(
    path: '/api/roles/{id}',
    summary: 'Obtener un rol',
    description: 'Obtiene un rol activo por su ID. Requiere autenticación.',
    security: [['bearerAuth' => []]],
    tags: ['Roles'],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID del rol.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Rol obtenido correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Rol no encontrado.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'ID de rol inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolValidationErrorResponse')
        ),
    ]
)]
#[OA\Post(
    path: '/api/roles',
    summary: 'Crear un rol',
    description: 'Crea un nuevo rol. El nombre debe tener entre 3 y 30 caracteres y contener únicamente letras y espacios. No se permiten nombres duplicados.',
    tags: ['Roles'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/RolCreateRequest')
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Rol creado exitosamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolCreatedResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'Solo un administrador puede crear roles.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 409,
            description: 'Ya existe un rol con ese nombre.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Datos del rol inválidos.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolValidationErrorResponse')
        ),
    ]
)]
#[OA\Put(
    path: '/api/roles/{id}',
    summary: 'Actualizar un rol',
    description: 'Actualiza el nombre de un rol existente. Solo los administradores pueden realizar esta operación. No se permiten nombres duplicados.',
    tags: ['Roles'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID del rol.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/RolUpdateRequest')
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Rol actualizado exitosamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolMutationResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'No se enviaron campos para actualizar o no se enviaron campos permitidos.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'Solo un administrador puede actualizar roles.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Rol no encontrado.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 409,
            description: 'El nombre ya está registrado.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Nombre o ID inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolValidationErrorResponse')
        ),
    ]
)]
#[OA\Delete(
    path: '/api/roles/{id}',
    summary: 'Eliminar un rol',
    description: 'Elimina lógicamente un rol. No se puede eliminar un rol que tenga usuarios asociados. Solo los administradores pueden realizar esta operación.',
    tags: ['Roles'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID del rol.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Rol eliminado exitosamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolMutationResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'Solo un administrador puede eliminar roles.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Rol no encontrado.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 409,
            description: 'No se puede eliminar el rol porque tiene usuarios asociados.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'ID de rol inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolValidationErrorResponse')
        ),
    ]
)]
#[OA\Post(
    path: '/api/roles/{id}/restaurar',
    summary: 'Restaurar un rol',
    description: 'Restaura un rol eliminado lógicamente. No se puede restaurar si ya existe un rol activo con el mismo nombre. Solo los administradores pueden realizar esta operación.',
    tags: ['Roles'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID del rol eliminado.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Rol restaurado exitosamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolMutationResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'Solo un administrador puede restaurar roles.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Rol eliminado no encontrado.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 409,
            description: 'Ya existe un rol activo con ese nombre.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'ID de rol inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/RolValidationErrorResponse')
        ),
    ]
)]
final class RolDocs
{
}