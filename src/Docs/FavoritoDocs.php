<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Favoritos',
    description: 'Gestión de propiedades favoritas de los usuarios'
)]

final class FavoritoDocs
{
    #[OA\Get(
        path: '/api/favoritos',
        operationId: 'listarFavoritos',
        summary: 'Obtener mis favoritos',
        description: 'Obtiene las propiedades favoritas del usuario autenticado, incluyendo sus relaciones.',
        tags: ['Favoritos'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Favoritos obtenidos correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoListResponse'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoErrorResponse'
                )
            ),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Post(
        path: '/api/favoritos',
        operationId: 'agregarFavorito',
        summary: 'Agregar una propiedad a favoritos',
        description: 'Agrega una propiedad a la lista de favoritos del usuario autenticado. No permite agregar propiedades propias ni duplicar favoritos.',
        tags: ['Favoritos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/FavoritoCreateRequest'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Propiedad agregada a favoritos',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoCreateResponse'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Solicitud incorrecta o intento de agregar una propiedad propia',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoErrorResponse'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoErrorResponse'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'La propiedad no existe',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoErrorResponse'
                )
            ),
            new OA\Response(
                response: 409,
                description: 'La propiedad ya está en favoritos',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoErrorResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoValidationErrorResponse'
                )
            ),
        ]
    )]
    public function store(): void
    {
    }

    #[OA\Get(
        path: '/api/usuarios/{id}/favoritos',
        operationId: 'listarFavoritosPorUsuario',
        summary: 'Obtener favoritos de un usuario',
        description: 'Obtiene los favoritos de un usuario específico. Solo el propio usuario o un administrador autorizado pueden realizar la consulta.',
        tags: ['Favoritos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del usuario cuyos favoritos se desean consultar',
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1
                ),
                example: 8
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Favoritos del usuario obtenidos correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoListResponse'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoErrorResponse'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'No tiene permiso para consultar los favoritos de este usuario',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoErrorResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'ID de usuario inválido',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoValidationErrorResponse'
                )
            ),
        ]
    )]
    public function indexByUsuario(): void
    {
    }

    #[OA\Delete(
        path: '/api/favoritos/propiedad/{propiedad_id}',
        operationId: 'eliminarFavorito',
        summary: 'Eliminar una propiedad de favoritos',
        description: 'Elimina una propiedad de la lista de favoritos del usuario autenticado.',
        tags: ['Favoritos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'propiedad_id',
                in: 'path',
                required: true,
                description: 'ID de la propiedad que se desea eliminar de favoritos',
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1
                ),
                example: 15
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Propiedad eliminada de favoritos',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoDeleteResponse'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'ID de usuario inválido',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoErrorResponse'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoErrorResponse'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'No tiene permiso para eliminar este favorito',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoErrorResponse'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'La propiedad no está en favoritos o no se pudo eliminar',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoErrorResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'ID de propiedad inválido',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/FavoritoValidationErrorResponse'
                )
            ),
        ]
    )]
    public function deleteByPropiedad(): void
    {
    }
}
