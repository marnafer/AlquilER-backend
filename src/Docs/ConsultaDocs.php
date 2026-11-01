<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Consultas',
    description: 'Gestión de consultas entre interesados y propietarios de propiedades'
)]
final class ConsultaDocs
{
    #[OA\Get(
        path: '/api/admin/consultas',
        summary: 'Listar todas las consultas',
        description: 'Permite a un administrador listar las consultas y aplicar filtros.',
        tags: ['Administración'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'usuario_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1),
                description: 'Filtrar por usuario interesado.'
            ),
            new OA\Parameter(
                name: 'propiedad_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1),
                description: 'Filtrar por propiedad.'
            ),
            new OA\Parameter(
                name: 'incluir_eliminados',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean'),
                description: 'Incluir consultas eliminadas junto con las activas.'
            ),
            new OA\Parameter(
                name: 'solo_eliminados',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean'),
                description: 'Mostrar únicamente consultas eliminadas.'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consultas obtenidas correctamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ConsultaListaAdmin'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'No tiene permisos para realizar esta operación.'),
        ]
    )]
    public function adminIndex(): void
    {
    }

    #[OA\Get(
        path: '/api/consultas',
        summary: 'Listar mis consultas',
        description: 'Obtiene las consultas del usuario autenticado, incluyendo los datos de la propiedad y sus imágenes.',
        tags: ['Consultas'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consultas obtenidas correctamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ConsultaLista'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Get(
        path: '/api/consultas/{id}',
        summary: 'Obtener una consulta',
        description: 'Obtiene el detalle de una consulta si el usuario autenticado está autorizado a participar.',
        tags: ['Consultas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
                description: 'ID de la consulta.'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consulta obtenida correctamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ConsultaDetalle'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'No tiene permisos para acceder a esta consulta.'),
            new OA\Response(response: 404, description: 'Consulta no encontrada.'),
        ]
    )]
    public function show(): void
    {
    }

    #[OA\Get(
        path: '/api/consultas/propiedad/{propiedadId}',
        summary: 'Listar consultas de una propiedad',
        description: 'Obtiene las consultas asociadas a una propiedad. El acceso está limitado al propietario o a un administrador.',
        tags: ['Consultas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'propiedadId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
                description: 'ID de la propiedad.'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consultas obtenidas correctamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ConsultaListaPorPropiedad'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'No tiene permisos para consultar esta propiedad.'),
            new OA\Response(response: 404, description: 'Propiedad no encontrada.'),
        ]
    )]
    public function indexByPropiedad(): void
    {
    }

    #[OA\Get(
        path: '/api/consultas/usuario/{usuarioId}',
        summary: 'Listar consultas de un usuario',
        description: 'Obtiene las consultas de un usuario. El usuario autenticado puede consultar sus propias consultas; un administrador puede consultar las de otros usuarios.',
        tags: ['Consultas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'usuarioId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
                description: 'ID del usuario interesado.'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consultas obtenidas correctamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ConsultaLista'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'No tiene permisos para consultar las consultas de este usuario.'),
        ]
    )]
    public function indexByUsuario(): void
    {
    }

    #[OA\Get(
        path: '/api/consultas/recibidas',
        summary: 'Listar consultas recibidas',
        description: 'Obtiene las consultas recibidas en las propiedades del propietario autenticado.',
        tags: ['Consultas'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consultas recibidas obtenidas correctamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ConsultaListaRecibidas'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
        ]
    )]
    public function indexRecibidas(): void
    {
    }

    #[OA\Post(
        path: '/api/consultas',
        summary: 'Crear una consulta',
        description: 'Crea una consulta sobre una propiedad. El usuario interesado se obtiene del token de autenticación. Puede incluir un mensaje inicial y un perfil del interesado.',
        tags: ['Consultas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ConsultaCreateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Consulta creada exitosamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Consulta creada exitosamente'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ConsultaCreateData'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Solicitud incorrecta.'),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'El interesado no puede consultar su propia propiedad.'),
            new OA\Response(response: 404, description: 'Propiedad o usuario no encontrado.'),
            new OA\Response(response: 422, description: 'Los datos enviados no superan la validación.'),
        ]
    )]
    public function store(): void
    {
    }

    #[OA\Put(
        path: '/api/consultas/{id}',
        summary: 'Actualizar el mensaje de una consulta',
        description: 'Actualiza el mensaje asociado a una consulta. Si todavía no tiene mensajes, crea uno.',
        tags: ['Consultas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
                description: 'ID de la consulta.'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ConsultaUpdateRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consulta actualizada exitosamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Consulta actualizada exitosamente'),
                        new OA\Property(property: 'data', type: 'object', example: new \stdClass()),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Solicitud incorrecta.'),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'No tiene permisos para actualizar esta consulta.'),
            new OA\Response(response: 404, description: 'Consulta no encontrada.'),
            new OA\Response(response: 422, description: 'Los datos enviados no superan la validación.'),
        ]
    )]
    public function update(): void
    {
    }

    #[OA\Delete(
        path: '/api/consultas/{id}',
        summary: 'Eliminar una consulta',
        description: 'Realiza una eliminación lógica de la consulta. Esta operación está restringida a administradores.',
        tags: ['Consultas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
                description: 'ID de la consulta.'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consulta eliminada exitosamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Consulta eliminada exitosamente'),
                        new OA\Property(property: 'data', type: 'object', example: new \stdClass()),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'No tiene permisos para eliminar consultas.'),
            new OA\Response(response: 404, description: 'Consulta no encontrada.'),
        ]
    )]
    public function delete(): void
    {
    }

    #[OA\Post(
        path: '/api/consultas/{id}/restaurar',
        summary: 'Restaurar una consulta eliminada',
        description: 'Restaura una consulta previamente eliminada. Esta operación está restringida a administradores.',
        tags: ['Consultas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1),
                description: 'ID de la consulta.'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consulta restaurada exitosamente.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Consulta restaurada exitosamente'),
                        new OA\Property(property: 'data', type: 'object', example: new \stdClass()),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado.'),
            new OA\Response(response: 403, description: 'No tiene permisos para restaurar consultas.'),
            new OA\Response(response: 404, description: 'Consulta no encontrada.'),
            new OA\Response(response: 409, description: 'La consulta no está eliminada y no puede restaurarse.'),
        ]
    )]
    public function restore(): void
    {
    }
}