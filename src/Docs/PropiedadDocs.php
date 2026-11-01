<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

final class PropiedadDocs
{
    #[OA\Get(
        path: '/api/propiedades',
        summary: 'Listar propiedades',
        description: 'Obtiene propiedades no eliminadas. Incluye propiedades disponibles y no disponibles. Permite filtrar, ordenar y paginar los resultados.',
        tags: ['Propiedades'],
        parameters: [
            new OA\Parameter(
                name: 'categoria_id',
                in: 'query',
                description: 'Identificadores de categorías. Puede repetirse para indicar varios valores.',
                style: 'form',
                explode: true,
                schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'integer', minimum: 1))
            ),
            new OA\Parameter(
                name: 'localidad_id',
                in: 'query',
                description: 'Identificadores de localidades.',
                style: 'form',
                explode: true,
                schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'integer', minimum: 1))
            ),
            new OA\Parameter(
                name: 'servicio_id',
                in: 'query',
                description: 'Identificadores de servicios. La propiedad debe contar con todos los servicios indicados.',
                style: 'form',
                explode: true,
                schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'integer', minimum: 1))
            ),
            new OA\Parameter(
                name: 'precio_min',
                in: 'query',
                description: 'Precio mínimo.',
                schema: new OA\Schema(type: 'number', minimum: 0)
            ),
            new OA\Parameter(
                name: 'precio_max',
                in: 'query',
                description: 'Precio máximo.',
                schema: new OA\Schema(type: 'number', minimum: 0)
            ),
            new OA\Parameter(
                name: 'cantidad_ambientes',
                in: 'query',
                description: 'Cantidad mínima de ambientes.',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'cantidad_dormitorios',
                in: 'query',
                description: 'Cantidad mínima de dormitorios.',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'cantidad_banos',
                in: 'query',
                description: 'Cantidad mínima de baños.',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'capacidad',
                in: 'query',
                description: 'Capacidad mínima de ocupantes.',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'acepta_mascotas',
                in: 'query',
                description: 'Filtrar por aceptación de mascotas.',
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'acepta_hijos',
                in: 'query',
                description: 'Filtrar por aceptación de hijos.',
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'disponible',
                in: 'query',
                description: 'Filtrar por disponibilidad.',
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'destacada',
                in: 'query',
                description: 'Filtrar por propiedades destacadas.',
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Número de página. Por defecto: 1.',
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Cantidad de resultados por página. Por defecto: 10. Máximo: 50.',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 10)
            ),
            new OA\Parameter(
                name: 'orden',
                in: 'query',
                description: 'Criterio de ordenamiento.',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['antiguedad_desc', 'antiguedad_asc', 'precio_asc', 'precio_desc'],
                    default: 'antiguedad_desc'
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado obtenido correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadListResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación en los filtros',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadValidationErrorResponse')
            ),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Get(
        path: '/api/propiedades/destacadas',
        summary: 'Listar propiedades destacadas',
        description: 'Obtiene propiedades destacadas que además están disponibles.',
        tags: ['Propiedades'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Propiedades destacadas obtenidas correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadDestacadasResponse')
            ),
        ]
    )]
    public function destacadas(): void
    {
    }

    #[OA\Get(
        path: '/api/admin/propiedades',
        summary: 'Listar propiedades desde administración',
        description: 'Obtiene todas las propiedades para administración. Permite incluir propiedades eliminadas o consultar únicamente las eliminadas.',
        tags: ['Administración'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'incluir_eliminados',
                in: 'query',
                description: 'Incluir propiedades eliminadas.',
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
            new OA\Parameter(
                name: 'solo_eliminados',
                in: 'query',
                description: 'Mostrar únicamente las propiedades eliminadas. Tiene prioridad sobre incluir_eliminados.',
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado administrativo obtenido correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadAdminListResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Acceso restringido a administradores',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
        ]
    )]
    public function adminIndex(): void
    {
    }

    #[OA\Get(
        path: '/api/propiedades/mis-propiedades',
        summary: 'Listar mis propiedades',
        description: 'Obtiene las propiedades pertenecientes al usuario autenticado, incluyendo imágenes y servicios.',
        tags: ['Propiedades'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Propiedades obtenidas correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadMisPropiedadesResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
        ]
    )]
    public function misPropiedades(): void
    {
    }

    #[OA\Get(
        path: '/api/propiedades/{id}',
        summary: 'Obtener propiedad',
        description: 'Obtiene el detalle de una propiedad, incluyendo categoría, localidad, servicios e imágenes.',
        tags: ['Propiedades'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Propiedad obtenida correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadDetalleResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Propiedad no encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'ID inválido',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadValidationErrorResponse')
            ),
        ]
    )]
    public function show(): void
    {
    }

    #[OA\Post(
        path: '/api/propiedades',
        summary: 'Crear propiedad',
        description: 'Registra una propiedad para el usuario autenticado. Los administradores pueden asignar un propietario y establecer si la propiedad es destacada.',
        tags: ['Propiedades'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PropiedadCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Propiedad creada correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadCreateResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Solicitud inválida',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'No autorizado',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadValidationErrorResponse')
            ),
        ]
    )]
    public function store(): void
    {
    }

    #[OA\Put(
        path: '/api/propiedades/{id}',
        summary: 'Actualizar propiedad',
        description: 'Actualiza los datos de una propiedad. El propietario o un administrador pueden realizar la operación. Los usuarios comunes no pueden modificar el campo destacada.',
        tags: ['Propiedades'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PropiedadUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Propiedad actualizada correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadEmptySuccessResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Solicitud inválida',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'No autorizado para actualizar esta propiedad',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Propiedad no encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadValidationErrorResponse')
            ),
        ]
    )]
    public function update(): void
    {
    }

    #[OA\Delete(
        path: '/api/propiedades/{id}',
        summary: 'Eliminar propiedad',
        description: 'Realiza la eliminación lógica de una propiedad. No permite eliminar propiedades con reservas activas.',
        tags: ['Propiedades'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Propiedad eliminada correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadEmptySuccessResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'No autorizado para eliminar esta propiedad',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Propiedad no encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'La propiedad tiene reservas activas',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'ID inválido',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadValidationErrorResponse')
            ),
        ]
    )]
    public function delete(): void
    {
    }

    #[OA\Post(
        path: '/api/propiedades/{id}/restaurar',
        summary: 'Restaurar propiedad',
        description: 'Restaura una propiedad previamente eliminada.',
        tags: ['Propiedades'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Propiedad restaurada correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadEmptySuccessResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'No autorizado para restaurar esta propiedad',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Propiedad eliminada no encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'ID inválido',
                content: new OA\JsonContent(ref: '#/components/schemas/PropiedadValidationErrorResponse')
            ),
        ]
    )]
    public function restore(): void
    {
    }
}
