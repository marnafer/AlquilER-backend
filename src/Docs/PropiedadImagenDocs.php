<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Imágenes de Propiedades',
    description: 'Consulta y gestión de imágenes asociadas a propiedades'
)]

#[OA\Get(
    path: '/api/propiedad-imagenes',
    summary: 'Listar imágenes de propiedades',
    description: 'Obtiene todas las imágenes registradas, incluyendo los datos de la propiedad asociada.',
    tags: ['Imágenes de Propiedades'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Listado de imágenes obtenido correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenListResponse'
            )
        ),
        new OA\Response(
            response: 500,
            description: 'Error interno del servidor',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenErrorResponse'
            )
        )
    ]
)]

#[OA\Get(
    path: '/api/propiedad-imagenes/{id}',
    summary: 'Obtener una imagen',
    description: 'Obtiene una imagen por su ID, incluyendo la propiedad asociada.',
    tags: ['Imágenes de Propiedades'],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la imagen',
            schema: new OA\Schema(type: 'integer', minimum: 1),
            example: 1
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Imagen obtenida correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenSingleResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Imagen no encontrada',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Error de validación',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenValidationErrorResponse'
            )
        )
    ]
)]

#[OA\Post(
    path: '/api/propiedad-imagenes',
    summary: 'Subir una imagen de propiedad',
    description: 'Carga una imagen y la asocia a una propiedad. La primera imagen de la propiedad se establece automáticamente como principal. Requiere autenticación y permisos sobre la propiedad, salvo administradores.',
    tags: ['Imágenes de Propiedades'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                ref: '#/components/schemas/PropiedadImagenCreateRequest'
            )
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Imagen guardada correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenCreatedResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permisos sobre esta propiedad',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Propiedad no encontrada',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Error de validación de los datos o de la imagen',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenValidationErrorResponse'
            )
        )
    ]
)]

#[OA\Put(
    path: '/api/propiedad-imagenes/{id}/principal',
    summary: 'Establecer imagen principal',
    description: 'Establece una imagen como principal de su propiedad. La imagen principal anterior deja de serlo. Requiere autenticación y permisos sobre la propiedad, salvo administradores.',
    tags: ['Imágenes de Propiedades'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la imagen',
            schema: new OA\Schema(type: 'integer', minimum: 1),
            example: 1
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Imagen principal actualizada',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenSingleResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permisos sobre esta imagen',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Imagen no encontrada',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Error de validación',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenValidationErrorResponse'
            )
        )
    ]
)]

#[OA\Delete(
    path: '/api/propiedad-imagenes/{id}',
    summary: 'Eliminar una imagen',
    description: 'Elimina una imagen de una propiedad. Si era la imagen principal, otra imagen disponible pasa a ser la principal. Requiere autenticación y permisos sobre la propiedad, salvo administradores.',
    tags: ['Imágenes de Propiedades'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la imagen',
            schema: new OA\Schema(type: 'integer', minimum: 1),
            example: 1
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Imagen eliminada correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenMutationResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permisos sobre esta imagen',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Imagen no encontrada',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Error de validación',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadImagenValidationErrorResponse'
            )
        )
    ]
)]

final class PropiedadImagenDocs
{
}
