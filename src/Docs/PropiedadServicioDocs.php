<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Propiedades - Servicios',
    description: 'Consulta y administración de los servicios asociados a propiedades'
)]
#[OA\Get(
    path: '/api/propiedades/{propiedadId}/servicios',
    operationId: 'listarServiciosDePropiedad',
    summary: 'Listar servicios de una propiedad',
    description: 'Obtiene los servicios asociados a una propiedad. No requiere autenticación.',
    tags: ['Propiedades - Servicios'],
    parameters: [
        new OA\Parameter(
            name: 'propiedadId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Servicios de la propiedad obtenidos correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioListResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'La propiedad no existe',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'ID de propiedad inválido',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Get(
    path: '/api/servicios/{servicioId}/propiedades',
    operationId: 'listarPropiedadesDeServicio',
    summary: 'Listar propiedades que ofrecen un servicio',
    description: 'Obtiene todas las propiedades asociadas a un servicio, incluyendo sus datos e imágenes. La respuesta no está paginada.',
    tags: ['Propiedades - Servicios'],
    parameters: [
        new OA\Parameter(
            name: 'servicioId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Propiedades obtenidas correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioPropiedadesListResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'El servicio no existe',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'ID de servicio inválido',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Post(
    path: '/api/propiedades/{propiedadId}/servicios',
    operationId: 'asignarServicioAPropiedad',
    summary: 'Asignar un servicio a una propiedad',
    description: 'Asigna un servicio a una propiedad. Solo el propietario o un administrador pueden realizar esta operación. Si el servicio ya está asignado, devuelve un conflicto.',
    tags: ['Propiedades - Servicios'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'propiedadId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/PropiedadServicioAsignarRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Servicio asignado correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioAsignarResponse'
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Cuerpo de solicitud obligatorio',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permisos para gestionar la propiedad',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'La propiedad o el servicio no existen',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 409,
            description: 'El servicio ya está asignado a la propiedad',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Datos inválidos',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Post(
    path: '/api/propiedades/{propiedadId}/servicios/multiple',
    operationId: 'asignarMultiplesServiciosAPropiedad',
    summary: 'Asignar varios servicios a una propiedad',
    description: 'Asigna varios servicios a una propiedad. Requiere al menos un ID válido y no permite IDs duplicados. Los servicios ya asignados se devuelven en duplicados. Solo el propietario o un administrador pueden realizar esta operación.',
    tags: ['Propiedades - Servicios'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'propiedadId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/PropiedadServicioMultiplesRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Resultado de la asignación múltiple',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioMultiplesResponse'
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Cuerpo de solicitud obligatorio',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permisos para gestionar la propiedad',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'La propiedad o alguno de los servicios no existe',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Lista de servicios inválida',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Put(
    path: '/api/propiedades/{propiedadId}/servicios',
    operationId: 'sincronizarServiciosDePropiedad',
    summary: 'Sincronizar servicios de una propiedad',
    description: 'Reemplaza los servicios asociados a una propiedad por la lista recibida. Los servicios omitidos se eliminan de la relación. Se permite una lista vacía para quitar todos los servicios. Solo el propietario o un administrador pueden realizar esta operación.',
    tags: ['Propiedades - Servicios'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'propiedadId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/PropiedadServicioSincronizarRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Servicios sincronizados correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioSincronizarResponse'
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Cuerpo de solicitud obligatorio',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permisos para gestionar la propiedad',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'La propiedad o alguno de los servicios no existe',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Lista de servicios inválida',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Delete(
    path: '/api/propiedades/{propiedadId}/servicios/{servicioId}',
    operationId: 'desasignarServicioDePropiedad',
    summary: 'Desasignar un servicio de una propiedad',
    description: 'Elimina la relación entre una propiedad y un servicio. Solo el propietario o un administrador pueden realizar esta operación.',
    tags: ['Propiedades - Servicios'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'propiedadId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
        new OA\Parameter(
            name: 'servicioId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Servicio desasignado correctamente',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioMutationResponse'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permisos para gestionar la propiedad',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'La propiedad, el servicio o la relación no existen',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'IDs inválidos',
            content: new OA\JsonContent(
                ref: '#/components/schemas/PropiedadServicioValidationErrorResponse'
            )
        ),
    ]
)]
final class PropiedadServicioDocs
{
}
