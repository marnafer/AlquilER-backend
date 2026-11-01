<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ConsultaPerfilInteresado',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'fecha_mudanza',
            type: 'string',
            format: 'date',
            nullable: true,
            example: '2026-12-01'
        ),
        new OA\Property(
            property: 'cantidad_ocupantes',
            type: 'integer',
            minimum: 1,
            maximum: 50,
            nullable: true,
            example: 3
        ),
        new OA\Property(
            property: 'tiene_mascotas',
            type: 'boolean',
            nullable: true,
            example: true
        ),
        new OA\Property(
            property: 'cantidad_mascotas',
            type: 'integer',
            minimum: 0,
            maximum: 20,
            nullable: true,
            example: 1
        ),
        new OA\Property(
            property: 'garantias',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                type: 'string',
                enum: [
                    'recibo_sueldo',
                    'garantia_propietaria',
                    'seguro_caucion',
                    'garante',
                ]
            ),
            example: ['recibo_sueldo', 'garante']
        ),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaUsuario',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'nombre', type: 'string', example: 'Juan'),
        new OA\Property(property: 'apellido', type: 'string', example: 'Pérez'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@email.com'),
        new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '343 4567890'),
        new OA\Property(property: 'domicilio', type: 'string', nullable: true, example: 'Crespo, Entre Ríos'),
        new OA\Property(property: 'rol_id', type: 'integer', example: 2),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'email_verification_expires_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaPropiedad',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 25),
        new OA\Property(property: 'titulo', type: 'string', example: 'Casa en alquiler'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true),
        new OA\Property(property: 'precio', type: 'number', format: 'float', example: 250000),
        new OA\Property(property: 'expensas', type: 'number', format: 'float', nullable: true, example: 15000),
        new OA\Property(property: 'direccion', type: 'string', example: 'San Martín 123'),
        new OA\Property(property: 'cantidad_ambientes', type: 'integer', nullable: true, example: 3),
        new OA\Property(property: 'cantidad_dormitorios', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'cantidad_banos', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'capacidad', type: 'integer', nullable: true, example: 4),
        new OA\Property(property: 'acepta_mascotas', type: 'boolean', example: true),
        new OA\Property(property: 'acepta_hijos', type: 'boolean', example: true),
        new OA\Property(property: 'requisitos_interesados', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
        new OA\Property(property: 'disponible', type: 'boolean', example: true),
        new OA\Property(property: 'destacada', type: 'boolean', example: false),
        new OA\Property(property: 'categoria_id', type: 'integer', example: 1),
        new OA\Property(property: 'localidad_id', type: 'integer', example: 1),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 8),
        new OA\Property(property: 'fecha_publicacion', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'imagen_url', type: 'string', nullable: true, example: '/uploads/propiedades/imagen.jpg'),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaImagen',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 15
        ),
        new OA\Property(
            property: 'propiedad_id',
            type: 'integer',
            example: 25
        ),
        new OA\Property(
            property: 'ruta',
            type: 'string',
            example: '/uploads/propiedades/imagen-15.jpg'
        ),
        new OA\Property(
            property: 'descripcion',
            type: 'string',
            nullable: true,
            example: 'Frente de la propiedad'
        ),
        new OA\Property(
            property: 'es_principal',
            type: 'boolean',
            example: true
        ),
    ]
)]
#[OA\Schema(
    schema: 'Consulta',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 45),
        new OA\Property(property: 'propiedad_id', type: 'integer', example: 25),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 12),
        new OA\Property(property: 'fecha_consulta', type: 'string', format: 'date-time', example: '2026-10-21T14:30:00'),
        new OA\Property(property: 'perfil_interesado', ref: '#/components/schemas/ConsultaPerfilInteresado', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaConPropiedad',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Consulta'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'propiedad', ref: '#/components/schemas/ConsultaPropiedad'),
                new OA\Property(property: 'ultimo_mensaje', type: 'string', nullable: true, example: '¿La propiedad continúa disponible?'),
                new OA\Property(property: 'ultima_actividad', type: 'string', format: 'date-time', nullable: true),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaConPropiedadEImagenes',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ConsultaConPropiedad'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'propiedad',
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/ConsultaPropiedad'),
                        new OA\Schema(
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'imagenes',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/ConsultaImagen')
                                ),
                            ]
                        ),
                    ]
                ),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaConUsuario',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Consulta'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'usuario', ref: '#/components/schemas/ConsultaUsuario'),
                new OA\Property(property: 'ultimo_mensaje', type: 'string', nullable: true, example: '¿La propiedad continúa disponible?'),
                new OA\Property(property: 'ultima_actividad', type: 'string', format: 'date-time', nullable: true),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaAdminItem',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Consulta'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'propiedad', ref: '#/components/schemas/ConsultaPropiedad'),
                new OA\Property(property: 'usuario', ref: '#/components/schemas/ConsultaUsuario'),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaDetalle',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Consulta'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'propiedad', ref: '#/components/schemas/ConsultaPropiedad'),
                new OA\Property(property: 'usuario', ref: '#/components/schemas/ConsultaUsuario'),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaLista',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ConsultaConPropiedadEImagenes')
        ),
        new OA\Property(property: 'total', type: 'integer', example: 5),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaListaPorPropiedad',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ConsultaConUsuario')
        ),
        new OA\Property(property: 'total', type: 'integer', example: 5),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaListaRecibidas',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ConsultaAdminItem')
        ),
        new OA\Property(property: 'total', type: 'integer', example: 5),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaListaAdmin',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ConsultaAdminItem')
        ),
        new OA\Property(property: 'total', type: 'integer', example: 5),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaCreateRequest',
    type: 'object',
    required: ['propiedad_id'],
    properties: [
        new OA\Property(property: 'propiedad_id', type: 'integer', minimum: 1, example: 25),
        new OA\Property(property: 'mensaje', type: 'string', minLength: 5, maxLength: 5000, nullable: true, example: 'Hola, quisiera saber si la propiedad continúa disponible.'),
        new OA\Property(property: 'perfil_interesado', ref: '#/components/schemas/ConsultaPerfilInteresado', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaUpdateRequest',
    type: 'object',
    required: ['mensaje'],
    properties: [
        new OA\Property(property: 'mensaje', type: 'string', minLength: 5, maxLength: 5000, example: 'Quisiera coordinar una visita para conocer la propiedad.'),
    ]
)]
#[OA\Schema(
    schema: 'ConsultaCreateData',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 45),
    ]
)]
final class ConsultaSchemas
{
}