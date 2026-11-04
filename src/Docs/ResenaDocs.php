<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/resenas',
    operationId: 'listarResenas',
    summary: 'Listar reseñas',
    description: 'Obtiene las reseñas con filtros opcionales. La consulta normal es pública. Si se solicita la papelera mediante solo_eliminados=true o incluir_eliminados=true, se requiere autenticación de administrador. Si ambos parámetros son true, prevalece solo_eliminados.',
    tags: ['Reseñas'],
    parameters: [
        new OA\Parameter(
            name: 'tipo',
            in: 'query',
            description: 'Tipo de reseña.',
            required: false,
            schema: new OA\Schema(type: 'string', enum: ['propiedad', 'inquilino'])
        ),
        new OA\Parameter(
            name: 'calificacion',
            in: 'query',
            description: 'Filtra por calificación exacta.',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 5)
        ),
        new OA\Parameter(
            name: 'calificacion_min',
            in: 'query',
            description: 'Calificación mínima.',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 5)
        ),
        new OA\Parameter(
            name: 'calificacion_max',
            in: 'query',
            description: 'Calificación máxima.',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 5)
        ),
        new OA\Parameter(
            name: 'calificador_id',
            in: 'query',
            description: 'Identificador del usuario que realizó la reseña.',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
        new OA\Parameter(
            name: 'reserva_id',
            in: 'query',
            description: 'Identificador de la reserva.',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
        new OA\Parameter(
            name: 'propiedad_id',
            in: 'query',
            description: 'Identificador de la propiedad relacionada con la reserva.',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
        new OA\Parameter(
            name: 'usuario_id',
            in: 'query',
            description: 'Identificador del inquilino relacionado con la reserva.',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
        new OA\Parameter(
            name: 'fecha_desde',
            in: 'query',
            description: 'Fecha inicial de publicación.',
            required: false,
            schema: new OA\Schema(type: 'string', format: 'date')
        ),
        new OA\Parameter(
            name: 'fecha_hasta',
            in: 'query',
            description: 'Fecha final de publicación.',
            required: false,
            schema: new OA\Schema(type: 'string', format: 'date')
        ),
        new OA\Parameter(
            name: 'solo_eliminados',
            in: 'query',
            description: 'Si es true, devuelve únicamente reseñas eliminadas. Requiere administrador.',
            required: false,
            schema: new OA\Schema(type: 'boolean', default: false)
        ),
        new OA\Parameter(
            name: 'incluir_eliminados',
            in: 'query',
            description: 'Si es true, incluye reseñas activas y eliminadas. Requiere administrador.',
            required: false,
            schema: new OA\Schema(type: 'boolean', default: false)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reseñas obtenidas correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaListResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No se proporcionó autenticación al solicitar la papelera.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'El usuario autenticado no tiene permisos de administrador.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
    ]
)]
#[OA\Get(
    path: '/api/resenas/{id}',
    operationId: 'obtenerResena',
    summary: 'Obtener una reseña',
    description: 'Obtiene una reseña activa por su identificador, incluyendo las relaciones cargadas de reserva, propiedad, usuario y calificador.',
    tags: ['Reseñas'],
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
            description: 'Reseña encontrada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'La reseña no existe o fue eliminada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Identificador inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaValidationErrorResponse')
        ),
    ]
)]
#[OA\Get(
    path: '/api/resenas/reserva/{reservaId}',
    operationId: 'obtenerResenasPorReserva',
    summary: 'Obtener reseñas de una reserva',
    description: 'Obtiene las reseñas activas asociadas a una reserva. La reserva debe existir.',
    tags: ['Reseñas'],
    parameters: [
        new OA\Parameter(
            name: 'reservaId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reseñas obtenidas correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaCollectionResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'La reserva no existe.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Identificador inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaValidationErrorResponse')
        ),
    ]
)]
#[OA\Get(
    path: '/api/resenas/propiedad/{propiedadId}',
    operationId: 'obtenerResenasPorPropiedad',
    summary: 'Obtener reseñas de una propiedad',
    description: 'Obtiene las reseñas activas de tipo propiedad asociadas a una propiedad e incluye el total y el promedio de calificaciones.',
    tags: ['Reseñas'],
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
            description: 'Reseñas obtenidas correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaPromedioResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Identificador inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaValidationErrorResponse')
        ),
    ]
)]
#[OA\Get(
    path: '/api/resenas/usuario/{usuarioId}',
    operationId: 'obtenerResenasPorUsuario',
    summary: 'Obtener reseñas de un usuario',
    description: 'Obtiene las reseñas activas de tipo inquilino asociadas a un usuario e incluye el total y el promedio de calificaciones.',
    tags: ['Reseñas'],
    parameters: [
        new OA\Parameter(
            name: 'usuarioId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reseñas obtenidas correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaPromedioResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Identificador inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaValidationErrorResponse')
        ),
    ]
)]
#[OA\Get(
    path: '/api/resenas/calificador/{calificadorId}',
    operationId: 'obtenerResenasPorCalificador',
    summary: 'Obtener reseñas realizadas por un usuario',
    description: 'Obtiene las reseñas activas realizadas por el usuario indicado como calificador.',
    tags: ['Reseñas'],
    parameters: [
        new OA\Parameter(
            name: 'calificadorId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reseñas obtenidas correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaCollectionResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Identificador inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaValidationErrorResponse')
        ),
    ]
)]
#[OA\Get(
    path: '/api/resenas/mis-propiedades',
    operationId: 'obtenerMisResenasDePropiedades',
    summary: 'Obtener reseñas de mis propiedades',
    description: 'Obtiene las reseñas activas de tipo propiedad correspondientes a las propiedades del usuario autenticado.',
    tags: ['Reseñas'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reseñas obtenidas correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaListResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
    ]
)]
#[OA\Get(
    path: '/api/resenas/como-inquilino',
    operationId: 'obtenerMisResenasComoInquilino',
    summary: 'Obtener mis reseñas como inquilino',
    description: 'Obtiene las reseñas activas de tipo inquilino recibidas por el usuario autenticado.',
    tags: ['Reseñas'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reseñas obtenidas correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaListResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
    ]
)]
#[OA\Post(
    path: '/api/resenas',
    operationId: 'crearResena',
    summary: 'Crear una reseña',
    description: 'Crea una reseña para una reserva finalizada. El tipo y el calificador se determinan automáticamente según el usuario autenticado y su participación en la reserva. Solo se permite una reseña por tipo y reserva, incluso si la anterior fue eliminada.',
    tags: ['Reseñas'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/ResenaCreateRequest')
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Reseña creada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'La reserva no está finalizada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'El usuario no participa en la reserva.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'La reserva no existe.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 409,
            description: 'Ya existe una reseña del mismo tipo para esta reserva.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Los datos enviados no superan la validación.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaValidationErrorResponse')
        ),
    ]
)]
#[OA\Put(
    path: '/api/resenas/{id}',
    operationId: 'actualizarResena',
    summary: 'Actualizar una reseña',
    description: 'Permite modificar la calificación y el comentario. Solo puede actualizarla el autor o un administrador. Debe enviarse al menos un campo modificable.',
    tags: ['Reseñas'],
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
        content: new OA\JsonContent(ref: '#/components/schemas/ResenaUpdateRequest')
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reseña actualizada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaNullResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'No se enviaron campos modificables.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permiso para modificar esta reseña.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'La reseña no existe o fue eliminada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Los datos enviados no superan la validación.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaValidationErrorResponse')
        ),
    ]
)]
#[OA\Delete(
    path: '/api/resenas/{id}',
    operationId: 'eliminarResena',
    summary: 'Eliminar una reseña',
    description: 'Realiza una eliminación lógica de la reseña. Solo los administradores pueden ejecutar esta operación.',
    tags: ['Reseñas'],
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
            description: 'Reseña eliminada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaMutationResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'Solo los administradores pueden eliminar reseñas.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'La reseña no existe o ya fue eliminada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Identificador inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaValidationErrorResponse')
        ),
    ]
)]
#[OA\Post(
    path: '/api/resenas/{id}/restaurar',
    operationId: 'restaurarResena',
    summary: 'Restaurar una reseña eliminada',
    description: 'Restaura una reseña eliminada lógicamente. Solo los administradores pueden ejecutar esta operación.',
    tags: ['Reseñas'],
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
            description: 'Reseña restaurada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaNullResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'Solo los administradores pueden restaurar reseñas.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'No se encontró una reseña eliminada con ese identificador.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Identificador inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaValidationErrorResponse')
        ),
    ]
)]
#[OA\Post(
    path: '/api/admin/resenas',
    operationId: 'crearResenaDesdeAdmin',
    summary: 'Crear una reseña como administrador',
    description: 'Crea una reseña para una reserva finalizada. El calificador debe ser el inquilino o el propietario de la reserva; el tipo de reseña se determina automáticamente.',
    tags: ['Administración'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/ResenaAdminCreateRequest')
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Reseña creada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'La reserva no está finalizada o el calificador no participa en ella.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'La operación está restringida a administradores.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'La reserva o el usuario calificador no existe.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 409,
            description: 'Ya existe una reseña del mismo tipo para esta reserva.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Los datos enviados no superan la validación.',
            content: new OA\JsonContent(ref: '#/components/schemas/ResenaValidationErrorResponse')
        ),
    ]
)]
final class ResenaDocs
{
}
