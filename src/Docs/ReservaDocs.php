<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/reservas',
    summary: 'Listar reservas',
    description: 'Obtiene las reservas según el alcance del usuario autenticado. Los usuarios pueden consultar sus propias reservas y las reservas de sus propiedades. Los administradores reciben todas las reservas.',
    tags: ['Reservas'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'estado',
            in: 'query',
            description: 'Filtrar por estado de la reserva.',
            required: false,
            schema: new OA\Schema(
                type: 'string',
                enum: ['pendiente', 'confirmada', 'rechazada', 'finalizada', 'cancelada']
            )
        ),
        new OA\Parameter(
            name: 'usuario_id',
            in: 'query',
            description: 'Filtrar por ID del usuario.',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
        new OA\Parameter(
            name: 'propiedad_id',
            in: 'query',
            description: 'Filtrar por ID de la propiedad.',
            required: false,
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
        new OA\Parameter(
            name: 'solo_eliminados',
            in: 'query',
            description: 'Incluir únicamente reservas eliminadas.',
            required: false,
            schema: new OA\Schema(type: 'boolean', default: false)
        ),
        new OA\Parameter(
            name: 'incluir_eliminados',
            in: 'query',
            description: 'Incluir también las reservas eliminadas.',
            required: false,
            schema: new OA\Schema(type: 'boolean', default: false)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reservas obtenidas correctamente.',
            content: new OA\JsonContent(
                oneOf: [
                    new OA\Schema(ref: '#/components/schemas/ReservaListResponse'),
                    new OA\Schema(ref: '#/components/schemas/ReservaAdminListResponse'),
                ]
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'No autorizado para acceder a las reservas solicitadas.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
    ]
)]
#[OA\Get(
    path: '/api/reservas/{id}',
    summary: 'Obtener una reserva',
    description: 'Obtiene el detalle de una reserva, incluyendo la propiedad, el usuario y las reseñas asociadas. Puede acceder el administrador, el usuario que realizó la reserva o el propietario de la propiedad.',
    tags: ['Reservas'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la reserva.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reserva obtenida correctamente.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Reserva'
                    ),
                    new OA\Property(property: 'message', type: 'string', nullable: true, example: 'Reserva encontrada'),
                ]
            )
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permiso para consultar esta reserva.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Reserva no encontrada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'ID de reserva inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaValidationErrorResponse')
        ),
    ]
)]
#[OA\Post(
    path: '/api/reservas',
    summary: 'Crear una reserva',
    description: 'Crea una reserva para una propiedad. El usuario se obtiene del token JWT y el estado inicial es pendiente. No se permite reservar una propiedad propia ni generar solapamientos con reservas confirmadas.',
    tags: ['Reservas'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/ReservaCreateRequest')
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Reserva creada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaCreatedResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'Solicitud incorrecta.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'No se permite reservar una propiedad propia.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Propiedad no encontrada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 409,
            description: 'La propiedad no está disponible o existe un solapamiento con otra reserva confirmada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'Datos de la reserva inválidos.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaValidationErrorResponse')
        ),
    ]
)]
#[OA\Put(
    path: '/api/reservas/{id}',
    summary: 'Actualizar el estado de una reserva',
    description: 'Permite al administrador modificar el estado de una reserva. Los estados admitidos para esta operación son pendiente, confirmada y rechazada.',
    tags: ['Reservas'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la reserva.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/ReservaUpdateRequest')
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reserva actualizada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaMutationResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'Solicitud incorrecta.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'Solo un administrador puede actualizar directamente el estado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Reserva no encontrada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'ID o estado inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaValidationErrorResponse')
        ),
    ]
)]
#[OA\Put(
    path: '/api/reservas/{id}/confirmar',
    summary: 'Confirmar una reserva',
    description: 'Permite al propietario de la propiedad o a un administrador confirmar una reserva pendiente. Se verifica nuevamente que no existan solapamientos con otras reservas confirmadas.',
    tags: ['Reservas'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la reserva.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reserva confirmada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaMutationResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'La reserva no se encuentra en un estado que permita confirmarla.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permiso para confirmar esta reserva.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Reserva no encontrada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 409,
            description: 'Existe un solapamiento con otra reserva confirmada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'ID de reserva inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaValidationErrorResponse')
        ),
    ]
)]
#[OA\Put(
    path: '/api/reservas/{id}/rechazar',
    summary: 'Rechazar una reserva',
    description: 'Permite al propietario de la propiedad o a un administrador rechazar una reserva pendiente.',
    tags: ['Reservas'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la reserva.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reserva rechazada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaMutationResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'La reserva no se encuentra en un estado que permita rechazarla.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permiso para rechazar esta reserva.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Reserva no encontrada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'ID de reserva inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaValidationErrorResponse')
        ),
    ]
)]
#[OA\Put(
    path: '/api/reservas/{id}/finalizar',
    summary: 'Finalizar una reserva',
    description: 'Permite al propietario de la propiedad o a un administrador finalizar una reserva confirmada.',
    tags: ['Reservas'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la reserva.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reserva finalizada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaMutationResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'La reserva no se encuentra en un estado que permita finalizarla.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permiso para finalizar esta reserva.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Reserva no encontrada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'ID de reserva inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaValidationErrorResponse')
        ),
    ]
)]
#[OA\Put(
    path: '/api/reservas/{id}/cancelar',
    summary: 'Cancelar una reserva',
    description: 'Permite al usuario que realizó la reserva o a un administrador cancelar una reserva pendiente.',
    tags: ['Reservas'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la reserva.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reserva cancelada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaMutationResponse')
        ),
        new OA\Response(
            response: 400,
            description: 'La reserva no se encuentra en un estado que permita cancelarla.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'No tiene permiso para cancelar esta reserva.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Reserva no encontrada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'ID de reserva inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaValidationErrorResponse')
        ),
    ]
)]
#[OA\Delete(
    path: '/api/reservas/{id}',
    summary: 'Eliminar una reserva',
    description: 'Elimina lógicamente una reserva. Esta operación está restringida a administradores.',
    tags: ['Reservas'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la reserva.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Operación de eliminación procesada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaMutationResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'Solo un administrador puede eliminar reservas.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'Reserva no encontrada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'ID de reserva inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaValidationErrorResponse')
        ),
    ]
)]
#[OA\Post(
    path: '/api/reservas/{id}/restaurar',
    summary: 'Restaurar una reserva',
    description: 'Restaura una reserva eliminada lógicamente. Esta operación está restringida a administradores.',
    tags: ['Reservas'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la reserva.',
            schema: new OA\Schema(type: 'integer', minimum: 1)
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Reserva restaurada correctamente.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaMutationResponse')
        ),
        new OA\Response(
            response: 401,
            description: 'No autenticado.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 403,
            description: 'Solo un administrador puede restaurar reservas.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 404,
            description: 'No se encontró una reserva eliminada con ese ID.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 409,
            description: 'La reserva existe, pero no está eliminada.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
        ),
        new OA\Response(
            response: 422,
            description: 'ID de reserva inválido.',
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaValidationErrorResponse')
        ),
    ]
)]
final class ReservaDocs
{
    #[OA\Post(
        path: '/api/admin/reservas',
        operationId: 'crearReservaDesdeAdmin',
        summary: 'Crear una reserva como administrador',
        description: 'Crea una reserva para el usuario y la propiedad indicados. El administrador puede establecerla como pendiente o confirmada; si omite estado, se crea como pendiente.',
        tags: ['Administración'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ReservaAdminCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Reserva creada correctamente.',
                content: new OA\JsonContent(ref: '#/components/schemas/ReservaCreatedResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'La propiedad no está disponible o las fechas se solapan con otra reserva confirmada.',
                content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado.',
                content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'La operación está restringida a administradores.',
                content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'La propiedad o el usuario no existe.',
                content: new OA\JsonContent(ref: '#/components/schemas/ReservaErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Los datos enviados no superan la validación.',
                content: new OA\JsonContent(ref: '#/components/schemas/ReservaValidationErrorResponse')
            ),
        ]
    )]
    public function storeAdmin(): void
    {
    }

}