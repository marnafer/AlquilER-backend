<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Localidades',
    description: 'Consulta y gestión de localidades'
)]
final class LocalidadDocs
{
    #[OA\Get(
        path: '/api/localidades',
        operationId: 'listarLocalidades',
        summary: 'Listar localidades',
        description: 'Obtiene las localidades disponibles. Permite consultar únicamente las eliminadas o incluirlas en el listado.',
        tags: ['Localidades'],
        parameters: [
            new OA\Parameter(
                name: 'solo_eliminados',
                in: 'query',
                required: false,
                description: 'Mostrar únicamente localidades eliminadas.',
                schema: new OA\Schema(type: 'boolean'),
                example: false
            ),
            new OA\Parameter(
                name: 'incluir_eliminados',
                in: 'query',
                required: false,
                description: 'Incluir localidades eliminadas en el listado.',
                schema: new OA\Schema(type: 'boolean'),
                example: true
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Localidades obtenidas correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadListResponse'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado al solicitar la papelera',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Acceso restringido a administradores al solicitar la papelera',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Get(
        path: '/api/localidades/{id}',
        operationId: 'obtenerLocalidad',
        summary: 'Obtener localidad',
        description: 'Obtiene una localidad según su identificador.',
        tags: ['Localidades'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Identificador de la localidad.',
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
                description: 'Localidad obtenida correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadSingleResponse'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Localidad no encontrada',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'ID de localidad inválido',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadValidationErrorResponse'
                )
            ),
        ]
    )]
    public function show(): void
    {
    }

    #[OA\Post(
        path: '/api/localidades',
        operationId: 'crearLocalidad',
        summary: 'Crear localidad',
        description: 'Registra una localidad asociada a una provincia. Requiere permisos de administrador.',
        tags: ['Localidades'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/LocalidadCreateRequest'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Localidad creada exitosamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadSingleResponse'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Acceso restringido a administradores',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Ya existe una localidad con ese nombre en la provincia',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación o provincia inexistente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadValidationErrorResponse'
                )
            ),
        ]
    )]
    public function store(): void
    {
    }

    #[OA\Put(
        path: '/api/localidades/{id}',
        operationId: 'actualizarLocalidad',
        summary: 'Actualizar localidad',
        description: 'Actualiza parcialmente una localidad. Requiere permisos de administrador y al menos un campo modificable.',
        tags: ['Localidades'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Identificador de la localidad.',
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
                ref: '#/components/schemas/LocalidadUpdateRequest'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Localidad actualizada exitosamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadMutationResponse'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'No se enviaron campos actualizables',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Acceso restringido a administradores',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Localidad no encontrada',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Ya existe otra localidad con ese nombre en la provincia',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación o provincia inexistente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadValidationErrorResponse'
                )
            ),
        ]
    )]
    public function update(): void
    {
    }

    #[OA\Delete(
        path: '/api/localidades/{id}',
        operationId: 'eliminarLocalidad',
        summary: 'Eliminar localidad',
        description: 'Realiza la eliminación lógica de una localidad. No permite eliminar localidades que tengan propiedades asociadas. Requiere permisos de administrador.',
        tags: ['Localidades'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Identificador de la localidad.',
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
                description: 'Localidad eliminada exitosamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadMutationResponse'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Acceso restringido a administradores',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Localidad no encontrada',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 409,
                description: 'No se puede eliminar porque tiene propiedades asociadas',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'ID de localidad inválido',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadValidationErrorResponse'
                )
            ),
        ]
    )]
    public function delete(): void
    {
    }

    #[OA\Post(
        path: '/api/localidades/{id}/restaurar',
        operationId: 'restaurarLocalidad',
        summary: 'Restaurar localidad',
        description: 'Restaura una localidad previamente eliminada. No permite restaurarla si ya existe otra localidad activa con el mismo nombre en la provincia. Requiere permisos de administrador.',
        tags: ['Localidades'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Identificador de la localidad eliminada.',
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
                description: 'Localidad restaurada correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadMutationResponse'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Acceso restringido a administradores',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Localidad eliminada no encontrada',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Ya existe una localidad activa con ese nombre en la provincia',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadErrorResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'ID de localidad inválido',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/LocalidadValidationErrorResponse'
                )
            ),
        ]
    )]
    public function restore(): void
    {
    }
}
