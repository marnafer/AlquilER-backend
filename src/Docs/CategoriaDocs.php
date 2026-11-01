<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

final class CategoriaDocs
{
    #[OA\Get(
        path: '/api/categorias',
        summary: 'Listar categorías',
        description: 'Obtiene las categorías ordenadas por identificador descendente. Permite consultar categorías eliminadas.',
        tags: ['Categorías'],
        parameters: [
            new OA\Parameter(
                name: 'solo_eliminados',
                in: 'query',
                description: 'Mostrar únicamente categorías eliminadas. Si se combina con incluir_eliminados, prevalece este parámetro.',
                required: false,
                schema: new OA\Schema(
                    type: 'boolean',
                    default: false
                )
            ),
            new OA\Parameter(
                name: 'incluir_eliminados',
                in: 'query',
                description: 'Incluir categorías eliminadas junto con las activas.',
                required: false,
                schema: new OA\Schema(
                    type: 'boolean',
                    default: false
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de categorías obtenido correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/CategoriaCollectionResponse'
                )
            ),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Get(
        path: '/api/categorias/{id}',
        summary: 'Obtener categoría',
        description: 'Obtiene una categoría por su identificador.',
        tags: ['Categorías'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoría obtenida correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/CategoriaResponse'
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Categoría no encontrada'
            ),
            new OA\Response(
                response: 422,
                description: 'ID inválido'
            ),
        ]
    )]
    public function show(): void
    {
    }

    #[OA\Post(
        path: '/api/categorias',
        summary: 'Crear categoría',
        description: 'Crea una nueva categoría. Solo los administradores pueden realizar esta operación.',
        tags: ['Categorías'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CategoriaCreateRequest'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Categoría creada exitosamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/CategoriaResponse'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
            new OA\Response(
                response: 403,
                description: 'Acceso restringido a administradores'
            ),
            new OA\Response(
                response: 409,
                description: 'Ya existe una categoría con ese nombre'
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación'
            ),
        ]
    )]
    public function store(): void
    {
    }

    #[OA\Put(
        path: '/api/categorias/{id}',
        summary: 'Actualizar categoría',
        description: 'Actualiza el nombre de una categoría. Solo los administradores pueden realizar esta operación.',
        tags: ['Categorías'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1
                )
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CategoriaUpdateRequest'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoría actualizada exitosamente'
            ),
            new OA\Response(
                response: 400,
                description: 'Solicitud inválida o sin campos actualizables'
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
            new OA\Response(
                response: 403,
                description: 'Acceso restringido a administradores'
            ),
            new OA\Response(
                response: 404,
                description: 'Categoría no encontrada'
            ),
            new OA\Response(
                response: 409,
                description: 'El nombre ya está registrado'
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación'
            ),
        ]
    )]
    public function update(): void
    {
    }

    #[OA\Delete(
        path: '/api/categorias/{id}',
        summary: 'Eliminar categoría',
        description: 'Realiza la eliminación lógica de una categoría. No permite eliminar categorías que tengan propiedades asociadas.',
        tags: ['Categorías'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoría eliminada exitosamente'
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
            new OA\Response(
                response: 403,
                description: 'Acceso restringido a administradores'
            ),
            new OA\Response(
                response: 404,
                description: 'Categoría no encontrada'
            ),
            new OA\Response(
                response: 409,
                description: 'La categoría tiene propiedades asociadas'
            ),
            new OA\Response(
                response: 422,
                description: 'ID inválido'
            ),
        ]
    )]
    public function delete(): void
    {
    }

    #[OA\Post(
        path: '/api/categorias/{id}/restaurar',
        summary: 'Restaurar categoría',
        description: 'Restaura una categoría previamente eliminada. No permite restaurarla si ya existe otra categoría activa con el mismo nombre.',
        tags: ['Categorías'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoría restaurada exitosamente'
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
            new OA\Response(
                response: 403,
                description: 'Acceso restringido a administradores'
            ),
            new OA\Response(
                response: 404,
                description: 'Categoría eliminada no encontrada'
            ),
            new OA\Response(
                response: 409,
                description: 'Ya existe una categoría activa con ese nombre'
            ),
            new OA\Response(
                response: 422,
                description: 'ID inválido'
            ),
        ]
    )]
    public function restore(): void
    {
    }
}
