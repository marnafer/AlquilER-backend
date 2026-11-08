<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/provincias',
    operationId: 'listarProvincias',
    summary: 'Listar provincias',
    description: 'Obtiene las provincias ordenadas alfabéticamente por nombre. Por defecto devuelve únicamente las provincias activas. Los parámetros solo_eliminados e incluir_eliminados permiten consultar la papelera y requieren autenticación de administrador.',
    tags: ['Provincias'],
    parameters: [
        new OA\Parameter(
            name: 'solo_eliminados',
            in: 'query',
            required: false,
            description: 'Si es true, devuelve únicamente las provincias eliminadas. Requiere administrador.',
            schema: new OA\Schema(type: 'boolean', default: false),
            example: true
        ),
        new OA\Parameter(
            name: 'incluir_eliminados',
            in: 'query',
            required: false,
            description: 'Si es true, incluye provincias activas y eliminadas. Requiere administrador.',
            schema: new OA\Schema(type: 'boolean', default: false),
            example: true
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Listado de provincias obtenido correctamente.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaListResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Se requiere autenticación para consultar la papelera.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'El usuario autenticado no tiene permisos de administrador.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
    ]
)]
#[OA\Get(
    path: '/api/provincias/{id}',
    operationId: 'obtenerProvincia',
    summary: 'Obtener provincia',
    description: 'Obtiene una provincia activa por su identificador.',
    tags: ['Provincias'],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identificador de la provincia.',
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
            description: 'Provincia obtenida correctamente.',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'success',
                        type: 'boolean',
                        example: true
                    ),
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/ProvinciaResponse'
                    ),
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Provincia no encontrada.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Identificador inválido.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Post(
    path: '/api/provincias',
    operationId: 'crearProvincia',
    summary: 'Crear provincia',
    description: 'Crea una provincia. El nombre debe tener entre 3 y 100 caracteres y contener únicamente letras y espacios. Solo los administradores pueden realizar esta operación.',
    tags: ['Provincias'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/ProvinciaCreateRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Provincia creada correctamente.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaCreatedResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permisos de administrador.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 409,
            description: 'Ya existe una provincia con ese nombre.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Los datos enviados no superaron la validación.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Put(
    path: '/api/provincias/{id}',
    operationId: 'actualizarProvincia',
    summary: 'Actualizar provincia',
    description: 'Actualiza el nombre de una provincia activa. Se debe enviar al menos un campo actualizable. Solo los administradores pueden realizar esta operación.',
    tags: ['Provincias'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identificador de la provincia.',
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
            ref: '#/components/schemas/ProvinciaUpdateRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Provincia actualizada correctamente.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaMutationResponse'
            )
        ),
        new OA\Response(
            response: 400,
            description: 'El cuerpo está vacío o no contiene campos actualizables.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permisos de administrador.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Provincia no encontrada.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 409,
            description: 'El nombre ya está registrado.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Los datos enviados no superaron la validación.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Delete(
    path: '/api/provincias/{id}',
    operationId: 'eliminarProvincia',
    summary: 'Eliminar provincia',
    description: 'Realiza la eliminación lógica de una provincia. No se puede eliminar una provincia que tenga localidades asociadas. Solo los administradores pueden realizar esta operación.',
    tags: ['Provincias'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identificador de la provincia.',
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
            description: 'Provincia eliminada correctamente.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaMutationResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permisos de administrador.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Provincia no encontrada.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 409,
            description: 'No se puede eliminar porque tiene localidades asociadas.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Identificador inválido.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Post(
    path: '/api/provincias/{id}/restaurar',
    operationId: 'restaurarProvincia',
    summary: 'Restaurar provincia',
    description: 'Restaura una provincia eliminada lógicamente. Si ya existe una provincia activa con el mismo nombre, la operación devuelve un conflicto. Solo los administradores pueden realizar esta operación.',
    tags: ['Provincias'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'Identificador de la provincia eliminada.',
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
            description: 'Provincia restaurada correctamente.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaMutationResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permisos de administrador.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Provincia eliminada no encontrada.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 409,
            description: 'Ya existe una provincia activa con ese nombre.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Identificador inválido.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProvinciaValidationErrorResponse'
            )
        ),
    ]
)]
final class ProvinciaDocs
{
}
