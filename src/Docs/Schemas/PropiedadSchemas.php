<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PropiedadResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10013),
        new OA\Property(property: 'titulo', type: 'string', example: 'Casa con patio'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Casa con quincho'),
        new OA\Property(property: 'precio', type: 'number', format: 'float', example: 500000),
        new OA\Property(property: 'expensas', type: 'number', format: 'float', example: 65000),
        new OA\Property(property: 'direccion', type: 'string', example: 'Los Álamos 670'),
        new OA\Property(property: 'cantidad_ambientes', type: 'integer', example: 3),
        new OA\Property(property: 'cantidad_dormitorios', type: 'integer', example: 2),
        new OA\Property(property: 'cantidad_banos', type: 'integer', example: 1),
        new OA\Property(property: 'capacidad', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'acepta_mascotas', type: 'boolean', example: false),
        new OA\Property(property: 'acepta_hijos', type: 'boolean', example: false),
        new OA\Property(
            property: 'requisitos_interesados',
            description: 'Requisitos para los interesados. Puede ser un objeto con requisitos o un arreglo vacío.',
            anyOf: [
                new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'fecha_disponible_desde', type: 'string', format: 'date', nullable: true, example: '2026-11-01'),
                        new OA\Property(property: 'max_ocupantes', type: 'integer', minimum: 1, maximum: 50, nullable: true, example: 4),
                        new OA\Property(
                            property: 'garantias_aceptadas',
                            type: 'array',
                            items: new OA\Items(
                                type: 'string',
                                enum: ['recibo_sueldo', 'garantia_propietaria', 'seguro_caucion', 'garante']
                            )
                        ),
                    ]
                ),
                new OA\Schema(type: 'array', maxItems: 0, items: new OA\Items(type: 'string'))
            ]
        ),
        new OA\Property(property: 'disponible', type: 'boolean', example: true),
        new OA\Property(property: 'destacada', type: 'boolean', example: false),
        new OA\Property(property: 'categoria_id', type: 'integer', example: 1),
        new OA\Property(property: 'localidad_id', type: 'integer', example: 1),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 12),
        new OA\Property(property: 'fecha_publicacion', type: 'string', format: 'date-time', nullable: true, example: '2026-10-30T01:36:23Z'),
        new OA\Property(property: 'imagen_url', type: 'string', nullable: true, example: '/uploads/propiedades/imagen.jpg'),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadCategoria',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Casa'),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadLocalidad',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Crespo'),
        new OA\Property(property: 'codigo_postal', type: 'string', example: '3116'),
        new OA\Property(property: 'provincia_id', type: 'integer', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicio',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 4),
        new OA\Property(property: 'nombre', type: 'string', example: 'Agua'),
        new OA\Property(
            property: 'pivot',
            type: 'object',
            properties: [
                new OA\Property(property: 'propiedad_id', type: 'integer', example: 10013),
                new OA\Property(property: 'servicio_id', type: 'integer', example: 4),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadImagen',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'propiedad_id', type: 'integer', example: 10013),
        new OA\Property(property: 'ruta', type: 'string', example: '/uploads/propiedades/imagen.jpg'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'es_principal', type: 'integer', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadAdminUsuario',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'nombre', type: 'string', example: 'Usuario'),
        new OA\Property(property: 'apellido', type: 'string', example: 'User'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@gmail.com'),
        new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '3436789098'),
        new OA\Property(property: 'domicilio', type: 'string', nullable: true, example: 'Jordania'),
        new OA\Property(property: 'rol_id', type: 'integer', example: 1),
        new OA\Property(property: 'email_verified_at', type: 'string', nullable: true, example: '2026-10-29 22:21:10'),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadDetalle',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PropiedadResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'categoria', ref: '#/components/schemas/PropiedadCategoria', nullable: true),
                new OA\Property(property: 'localidad', ref: '#/components/schemas/PropiedadLocalidad', nullable: true),
                new OA\Property(
                    property: 'servicios',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PropiedadServicio')
                ),
                new OA\Property(
                    property: 'imagenes',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PropiedadImagen')
                ),
                new OA\Property(
                    property: 'imagen_principal',
                    ref: '#/components/schemas/PropiedadImagen',
                    nullable: true
                ),
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'PropiedadMisPropiedadesItem',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PropiedadResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'imagenes',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PropiedadImagen')
                ),
                new OA\Property(
                    property: 'imagen_principal',
                    ref: '#/components/schemas/PropiedadImagen',
                    nullable: true
                ),
                new OA\Property(
                    property: 'servicios',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PropiedadServicio')
                ),
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'PropiedadAdminItem',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PropiedadResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'usuario', ref: '#/components/schemas/PropiedadAdminUsuario', nullable: true),
                new OA\Property(property: 'categoria', ref: '#/components/schemas/PropiedadCategoria', nullable: true),
                new OA\Property(property: 'localidad', ref: '#/components/schemas/PropiedadLocalidad', nullable: true),
                new OA\Property(
                    property: 'imagenes',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PropiedadImagen')
                ),
                new OA\Property(
                    property: 'imagen_principal',
                    ref: '#/components/schemas/PropiedadImagen',
                    nullable: true
                ),
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'PropiedadPagination',
    type: 'object',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 10),
        new OA\Property(property: 'last_page', type: 'integer', example: 1001),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadCreateRequest',
    type: 'object',
    required: [
        'titulo',
        'precio',
        'direccion',
        'cantidad_ambientes',
        'cantidad_dormitorios',
        'cantidad_banos',
        'categoria_id',
        'localidad_id',
    ],
    properties: [
        new OA\Property(property: 'titulo', type: 'string', minLength: 3, maxLength: 150, example: 'Casa con patio'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, maxLength: 5000, example: 'Casa con quincho'),
        new OA\Property(property: 'precio', type: 'number', exclusiveMinimum: 0, example: 500000),
        new OA\Property(property: 'expensas', type: 'number', minimum: 0, default: 0, example: 65000),
        new OA\Property(property: 'direccion', type: 'string', minLength: 3, maxLength: 125, example: 'Los Álamos 670'),
        new OA\Property(property: 'cantidad_ambientes', type: 'integer', minimum: 1, example: 3),
        new OA\Property(property: 'cantidad_dormitorios', type: 'integer', minimum: 1, example: 2),
        new OA\Property(property: 'cantidad_banos', type: 'integer', minimum: 1, example: 1),
        new OA\Property(property: 'capacidad', type: 'integer', minimum: 1, nullable: true, example: 5),
        new OA\Property(property: 'acepta_mascotas', type: 'boolean', default: false, example: false),
        new OA\Property(property: 'acepta_hijos', type: 'boolean', default: false, example: false),
        new OA\Property(
                        property: 'requisitos_interesados',
                        description: 'Objeto con los requisitos del interesado o arreglo vacío para limpiarlos.',
                        anyOf: [
                            new OA\Schema(
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'fecha_disponible_desde',
                                        type: 'string',
                                        format: 'date',
                                        nullable: true
                                    ),
                                    new OA\Property(
                                        property: 'max_ocupantes',
                                        type: 'integer',
                                        minimum: 1,
                                        maximum: 50,
                                        nullable: true
                                    ),
                                    new OA\Property(
                                        property: 'garantias_aceptadas',
                                        type: 'array',
                                        items: new OA\Items(
                                            type: 'string',
                                            enum: [
                                                'recibo_sueldo',
                                                'garantia_propietaria',
                                                'seguro_caucion',
                                                'garante'
                                            ]
                                        )
                                    ),
                                ]
                            ),
                            new OA\Schema(
                                type: 'array',
                                maxItems: 0,
                                items: new OA\Items(type: 'string')
                            )
                        ]
                    ),
        new OA\Property(property: 'disponible', type: 'boolean', default: true),
        new OA\Property(property: 'destacada', type: 'boolean', default: false, description: 'Solo los administradores pueden asignar esta propiedad como destacada.'),
        new OA\Property(property: 'categoria_id', type: 'integer', minimum: 1, example: 1),
        new OA\Property(property: 'localidad_id', type: 'integer', minimum: 1, example: 1),
        new OA\Property(
            property: 'servicios',
            type: 'array',
            items: new OA\Items(type: 'integer', minimum: 1),
            example: [4, 6, 7]
        ),
        new OA\Property(
            property: 'propietario_id',
            type: 'integer',
            minimum: 1,
            nullable: true,
            description: 'Identificador del propietario. Solo aplicable a administradores.',
            example: 12
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadUpdateRequest',
    type: 'object',
    minProperties: 1,
    properties: [
        new OA\Property(property: 'titulo', type: 'string', minLength: 3, maxLength: 150, example: 'Casa renovada'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, maxLength: 5000),
        new OA\Property(property: 'precio', type: 'number', exclusiveMinimum: 0, example: 550000),
        new OA\Property(property: 'expensas', type: 'number', minimum: 0),
        new OA\Property(property: 'direccion', type: 'string', minLength: 3, maxLength: 125),
        new OA\Property(property: 'cantidad_ambientes', type: 'integer', minimum: 1),
        new OA\Property(property: 'cantidad_dormitorios', type: 'integer', minimum: 1),
        new OA\Property(property: 'cantidad_banos', type: 'integer', minimum: 1),
        new OA\Property(property: 'capacidad', type: 'integer', minimum: 1, nullable: true),
        new OA\Property(property: 'acepta_mascotas', type: 'boolean'),
        new OA\Property(property: 'acepta_hijos', type: 'boolean'),
        new OA\Property(property: 'requisitos_interesados', type: 'object'),
        new OA\Property(property: 'disponible', type: 'boolean'),
        new OA\Property(property: 'destacada', type: 'boolean', description: 'Solo los administradores pueden modificar este campo.'),
        new OA\Property(property: 'categoria_id', type: 'integer', minimum: 1),
        new OA\Property(property: 'localidad_id', type: 'integer', minimum: 1),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadListData',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PropiedadResponse')
        ),
        new OA\Property(property: 'total', type: 'integer', example: 10008),
        new OA\Property(property: 'pagination', ref: '#/components/schemas/PropiedadPagination'),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadDestacadasData',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PropiedadResponse')
        ),
        new OA\Property(property: 'total', type: 'integer', example: 5),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadMisPropiedadesData',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PropiedadMisPropiedadesItem')
        ),
        new OA\Property(property: 'total', type: 'integer', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadAdminListData',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PropiedadAdminItem')
        ),
        new OA\Property(property: 'total', type: 'integer', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadSuccessResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object'),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadListResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PropiedadSuccessResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/PropiedadListData')
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'PropiedadDestacadasResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PropiedadSuccessResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/PropiedadDestacadasData')
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'PropiedadMisPropiedadesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PropiedadSuccessResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/PropiedadMisPropiedadesData')
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'PropiedadAdminListResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PropiedadSuccessResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/PropiedadAdminListData')
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'PropiedadDetalleResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', ref: '#/components/schemas/PropiedadDetalle'),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadCreateResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Propiedad creada exitosamente'),
        new OA\Property(property: 'data', ref: '#/components/schemas/PropiedadResponse'),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadEmptySuccessResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'), example: []),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'error', type: 'string', example: 'Error interno del servidor'),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadValidationErrorResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PropiedadErrorResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'validation_errors',
                    type: 'object',
                    additionalProperties: new OA\AdditionalProperties(
                        type: 'array',
                        items: new OA\Items(type: 'string')
                    ),
                    example: ['titulo' => ['El título es obligatorio']]
                )
            ]
        )
    ]
)]
final class PropiedadSchemas
{
}
