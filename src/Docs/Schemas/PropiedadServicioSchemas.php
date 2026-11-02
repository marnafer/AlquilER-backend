<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PropiedadServicioServicio',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 5),
        new OA\Property(property: 'nombre', type: 'string', example: 'Luz'),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioPropiedadImagen',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'propiedad_id', type: 'integer', example: 4),
        new OA\Property(
            property: 'ruta',
            type: 'string',
            nullable: true,
            example: '/uploads/propiedades/imagen.jpg'
        ),
        new OA\Property(
            property: 'descripcion',
            type: 'string',
            nullable: true,
            example: null
        ),
        new OA\Property(
            property: 'es_principal',
            type: 'integer',
            example: 1
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioPropiedad',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 4),
        new OA\Property(
            property: 'titulo',
            type: 'string',
            example: 'Casa quinta actualizada'
        ),
        new OA\Property(
            property: 'descripcion',
            type: 'string',
            example: 'Descripción modificada'
        ),
        new OA\Property(property: 'precio', type: 'number', format: 'float', example: 200000),
        new OA\Property(property: 'expensas', type: 'number', format: 'float', example: 7000),
        new OA\Property(property: 'direccion', type: 'string', example: 'San Martin 456'),
        new OA\Property(property: 'cantidad_ambientes', type: 'integer', example: 6),
        new OA\Property(property: 'cantidad_dormitorios', type: 'integer', example: 4),
        new OA\Property(property: 'cantidad_banos', type: 'integer', example: 2),
        new OA\Property(
            property: 'capacidad',
            type: 'integer',
            nullable: true,
            example: 8
        ),
        new OA\Property(property: 'acepta_mascotas', type: 'boolean', example: true),
        new OA\Property(property: 'acepta_hijos', type: 'boolean', example: true),
        new OA\Property(
                        property: 'requisitos_interesados',
                        anyOf: [
                            new OA\Schema(
                                type: 'object',
                                nullable: true,
                                properties: [
                                    new OA\Property(
                                        property: 'fecha_disponible_desde',
                                        type: 'string',
                                        format: 'date',
                                        nullable: true,
                                        example: '2026-12-01'
                                    ),
                                    new OA\Property(
                                        property: 'max_ocupantes',
                                        type: 'integer',
                                        minimum: 1,
                                        maximum: 50,
                                        nullable: true,
                                        example: 4
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
                                ],
                                additionalProperties: false
                            ),
                            new OA\Schema(
                                type: 'array',
                                maxItems: 0,
                                items: new OA\Items(type: 'string')
                            ),
                        ]
        ),
        new OA\Property(property: 'disponible', type: 'boolean', example: true),
        new OA\Property(property: 'destacada', type: 'boolean', example: true),
        new OA\Property(property: 'categoria_id', type: 'integer', example: 1),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 6),
        new OA\Property(property: 'localidad_id', type: 'integer', example: 1),
        new OA\Property(
            property: 'fecha_publicacion',
            type: 'string',
            format: 'date-time',
            example: '2026-10-28T17:11:36.000000Z'
        ),
        new OA\Property(
            property: 'imagen_url',
            type: 'string',
            nullable: true,
            example: null
        ),
        new OA\Property(
            property: 'imagen_principal',
            ref: '#/components/schemas/PropiedadServicioPropiedadImagen',
            nullable: true
        ),
        new OA\Property(
            property: 'imagenes',
            type: 'array',
            items: new OA\Items(
                ref: '#/components/schemas/PropiedadServicioPropiedadImagen'
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioRelacion',
    type: 'object',
    required: ['id', 'propiedad_id', 'servicio_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 59),
        new OA\Property(property: 'propiedad_id', type: 'integer', example: 15),
        new OA\Property(property: 'servicio_id', type: 'integer', example: 5),
        new OA\Property(
            property: 'servicio',
            ref: '#/components/schemas/PropiedadServicioServicio'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioRelacionConPropiedad',
    type: 'object',
    required: ['id', 'propiedad_id', 'servicio_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'propiedad_id', type: 'integer', example: 4),
        new OA\Property(property: 'servicio_id', type: 'integer', example: 15),
        new OA\Property(
            property: 'propiedad',
            ref: '#/components/schemas/PropiedadServicioPropiedad'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioListResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        ref: '#/components/schemas/PropiedadServicioRelacion'
                    )
                ),
                new OA\Property(property: 'total', type: 'integer', example: 3),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioPropiedadesListResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        ref: '#/components/schemas/PropiedadServicioRelacionConPropiedad'
                    )
                ),
                new OA\Property(property: 'total', type: 'integer', example: 2),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioAsignarRequest',
    type: 'object',
    required: ['servicio_id'],
    properties: [
        new OA\Property(
            property: 'servicio_id',
            type: 'integer',
            minimum: 1,
            example: 5
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioMultiplesRequest',
    type: 'object',
    required: ['servicio_ids'],
    properties: [
        new OA\Property(
            property: 'servicio_ids',
            type: 'array',
            minItems: 1,
            uniqueItems: true,
            items: new OA\Items(type: 'integer', minimum: 1),
            example: [1, 2, 3]
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioSincronizarRequest',
    type: 'object',
    required: ['servicio_ids'],
    properties: [
        new OA\Property(
            property: 'servicio_ids',
            type: 'array',
            uniqueItems: true,
            items: new OA\Items(type: 'integer', minimum: 1),
            example: [1, 3]
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioAsignarResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'propiedad_id', type: 'integer', example: 15),
                new OA\Property(property: 'servicio_id', type: 'integer', example: 5),
            ]
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Servicio asignado correctamente'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioMultiplesResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'asignados',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [1, 2]
                ),
                new OA\Property(
                    property: 'duplicados',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [3]
                ),
                new OA\Property(
                    property: 'errores',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: []
                ),
            ]
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Servicios asignados correctamente'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioSincronizarResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'agregados',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [3]
                ),
                new OA\Property(
                    property: 'eliminados',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [2]
                ),
                new OA\Property(
                    property: 'mantenidos',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [1]
                ),
            ]
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Servicios sincronizados correctamente'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioMutationResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Servicio desasignado correctamente'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'error',
            type: 'string',
            example: 'La propiedad no existe'
        ),
    ]
)]
#[OA\Schema(
    schema: 'PropiedadServicioValidationErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'error',
            type: 'string',
            example: 'Error de validación'
        ),
        new OA\Property(
            property: 'validation_errors',
            type: 'object',
            additionalProperties: true
        ),
    ]
)]
final class PropiedadServicioSchemas
{
}
