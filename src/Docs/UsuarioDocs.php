<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

final class UsuarioDocs
{
    #[OA\Get(
        path: '/api/usuarios',
        summary: 'Listar usuarios',
        description: 'Obtiene el listado de usuarios. Permite filtrar usuarios eliminados.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'solo_eliminados',
                in: 'query',
                required: false,
                description: 'Mostrar únicamente usuarios eliminados.',
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'incluir_eliminados',
                in: 'query',
                required: false,
                description: 'Incluir usuarios eliminados en el listado.',
                schema: new OA\Schema(type: 'boolean')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado obtenido correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/UsuarioListResponse'
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso restringido a administradores'),
        ]
    )]
    public function index(): void
    {
    }

    #[OA\Get(
        path: '/api/usuarios/{id}',
        summary: 'Obtener usuario',
        description: 'Obtiene los datos de un usuario según su identificador.',
        tags: ['Usuarios'],
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
                description: 'Usuario obtenido correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/UsuarioResponse'
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
            new OA\Response(response: 422, description: 'ID inválido'),
        ]
    )]
    public function show(): void
    {
    }

    #[OA\Get(
        path: '/api/usuarios/me',
        summary: 'Obtener perfil propio',
        description: 'Obtiene los datos del usuario autenticado, incluyendo su rol.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil obtenido correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/UsuarioResponse'
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
        ]
    )]
    public function profile(): void
    {
    }

    #[OA\Get(
        path: '/api/usuarios/{id}/perfil-publico',
        summary: 'Obtener perfil público',
        description: 'Obtiene el perfil público de un usuario, sus calificaciones y reseñas.',
        tags: ['Usuarios'],
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
                description: 'Perfil público obtenido correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/UsuarioPublicProfileResponse'
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado para consultar este perfil'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
            new OA\Response(response: 422, description: 'ID inválido'),
        ]
    )]
    public function publicProfile(): void
    {
    }

    #[OA\Post(
        path: '/api/admin/usuarios',
        summary: 'Crear usuario desde administración',
        description: 'Permite a un administrador registrar un usuario y asignarle un rol.',
        tags: ['Usuarios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/UsuarioAdminCreateRequest'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario creado correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/UsuarioResponse'
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso restringido a administradores'),
            new OA\Response(response: 409, description: 'El email ya está registrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function storeAdmin(): void
    {
    }

    #[OA\Put(
        path: '/api/usuarios/{id}',
        summary: 'Actualizar usuario',
        description: 'Actualiza parcialmente los datos de un usuario. Para cambiar la propia contraseña se debe enviar contrasena y contrasena_actual.',
        tags: ['Usuarios'],
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
            content: new OA\JsonContent(
                ref: '#/components/schemas/UsuarioUpdateRequest'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado correctamente'),
            new OA\Response(response: 400, description: 'Solicitud inválida'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado para actualizar este usuario'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
            new OA\Response(response: 409, description: 'El email ya está registrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(): void
    {
    }

    #[OA\Delete(
        path: '/api/usuarios/{id}',
        summary: 'Eliminar usuario',
        description: 'Realiza la eliminación lógica de un usuario.',
        tags: ['Usuarios'],
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
            new OA\Response(response: 200, description: 'Usuario eliminado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado para eliminar este usuario'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
            new OA\Response(response: 422, description: 'ID inválido'),
        ]
    )]
    public function delete(): void
    {
    }

    #[OA\Post(
        path: '/api/usuarios/{id}/restaurar',
        summary: 'Restaurar usuario',
        description: 'Restaura un usuario previamente eliminado.',
        tags: ['Usuarios'],
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
            new OA\Response(response: 200, description: 'Usuario restaurado correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Acceso restringido a administradores'),
            new OA\Response(response: 404, description: 'Usuario eliminado no encontrado'),
            new OA\Response(response: 409, description: 'Ya existe un usuario activo con ese email'),
            new OA\Response(response: 422, description: 'ID inválido'),
        ]
    )]
    public function restore(): void
    {
    }
}
