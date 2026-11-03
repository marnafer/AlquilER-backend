<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ReservaUsuario',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'nombre', type: 'string', example: 'Propietario'),
        new OA\Property(property: 'apellido', type: 'string', example: 'Prop'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'prop@gmail.com'),
        new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '3624123456'),
        new OA\Property(property: 'domicilio', type: 'string', nullable: true, example: 'Prop'),
        new OA\Property(property: 'rol_id', type: 'integer', example: 2),
        new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true, example: null),
    ]
)]
#[OA\Schema(
    schema: 'ReservaLocalidad',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Crespo'),
        new OA\Property(property: 'codigo_postal', type: 'string', example: '3116'),
        new OA\Property(property: 'provincia_id', type: 'integer', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'ReservaImagen',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'propiedad_id', type: 'integer', example: 1),
        new OA\Property(property: 'ruta', type: 'string', example: 'propiedades/1/imagen-1.webp'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Frente de la propiedad'),
        new OA\Property(property: 'es_principal', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'ReservaPropiedad',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'titulo', type: 'string', example: 'Casa prueba'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Prueba'),
        new OA\Property(property: 'precio', type: 'number', format: 'float', example: 150000),
        new OA\Property(property: 'expensas', type: 'number', format: 'float', example: 0),
        new OA\Property(property: 'direccion', type: 'string', example: 'Prueba'),
        new OA\Property(property: 'cantidad_ambientes', type: 'integer', example: 2),
        new OA\Property(property: 'cantidad_dormitorios', type: 'integer', example: 1),
        new OA\Property(property: 'cantidad_banos', type: 'integer', example: 1),
        new OA\Property(property: 'capacidad', type: 'integer', example: 2),
        new OA\Property(property: 'acepta_mascotas', type: 'boolean', example: true),
        new OA\Property(property: 'acepta_hijos', type: 'boolean', example: true),
        new OA\Property(
            property: 'requisitos_interesados',
            type: 'object',
            nullable: true,
            additionalProperties: true,
            example: null
        ),
        new OA\Property(property: 'disponible', type: 'boolean', example: false),
        new OA\Property(property: 'destacada', type: 'boolean', example: false),
        new OA\Property(property: 'categoria_id', type: 'integer', example: 1),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 2),
        new OA\Property(property: 'localidad_id', type: 'integer', example: 1),
        new OA\Property(property: 'fecha_publicacion', type: 'string', format: 'date-time', nullable: true, example: '2026-10-28T17:11:36.000000Z'),
        new OA\Property(property: 'imagen_url', type: 'string', nullable: true, example: null),
        new OA\Property(
            property: 'localidad',
            ref: '#/components/schemas/ReservaLocalidad',
            nullable: true
        ),
        new OA\Property(
            property: 'imagen_principal',
            ref: '#/components/schemas/ReservaImagen',
            nullable: true
        ),
        new OA\Property(
            property: 'imagenes',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ReservaImagen')
        ),
    ]
)]
#[OA\Schema(
    schema: 'ReservaResena',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'reserva_id', type: 'integer', example: 2),
        new OA\Property(property: 'tipo', type: 'string', enum: ['propiedad', 'inquilino'], example: 'propiedad'),
        new OA\Property(property: 'calificador_id', type: 'integer', example: 2),
        new OA\Property(property: 'calificacion', type: 'integer', minimum: 1, maximum: 5, example: 5),
        new OA\Property(property: 'comentario', type: 'string', nullable: true, example: 'Muy buena experiencia'),
        new OA\Property(property: 'fecha_publicacion', type: 'string', format: 'date-time', nullable: true, example: '2026-10-28T17:11:36.000000Z'),
    ]
)]
#[OA\Schema(
    schema: 'Reserva',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'propiedad_id', type: 'integer', example: 1),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 2),
        new OA\Property(property: 'fecha_inicio_alquiler', type: 'string', format: 'date', example: '2026-07-01'),
        new OA\Property(property: 'fecha_fin_alquiler', type: 'string', format: 'date', nullable: true, example: '2027-07-01'),
        new OA\Property(
            property: 'estado',
            type: 'string',
            enum: ['pendiente', 'confirmada', 'rechazada', 'finalizada', 'cancelada'],
            example: 'finalizada'
        ),
        new OA\Property(property: 'fecha_reserva', type: 'string', format: 'date-time', example: '2026-06-13T21:55:23.000000Z'),
        new OA\Property(property: 'fecha_confirmacion', type: 'string', format: 'date-time', nullable: true, example: null),
        new OA\Property(
            property: 'propiedad',
            ref: '#/components/schemas/ReservaPropiedad',
            nullable: true
        ),
        new OA\Property(
            property: 'usuario',
            ref: '#/components/schemas/ReservaUsuario',
            nullable: true
        ),
        new OA\Property(
            property: 'resenas',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ReservaResena')
        ),
    ]
)]
#[OA\Schema(
    schema: 'ReservaCreateRequest',
    type: 'object',
    required: ['propiedad_id', 'fecha_inicio_alquiler', 'fecha_fin_alquiler'],
    properties: [
        new OA\Property(property: 'propiedad_id', type: 'integer', example: 1),
        new OA\Property(property: 'fecha_inicio_alquiler', type: 'string', format: 'date', example: '2026-07-01'),
        new OA\Property(property: 'fecha_fin_alquiler', type: 'string', format: 'date', example: '2027-07-01'),
    ]
)]
#[OA\Schema(
    schema: 'ReservaUpdateRequest',
    type: 'object',
    required: ['estado'],
    properties: [
        new OA\Property(
            property: 'estado',
            type: 'string',
            enum: ['pendiente', 'confirmada', 'rechazada'],
            example: 'confirmada'
        ),
    ]
)]
#[OA\Schema(
    schema: 'ReservaCreatedResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 2),
            ]
        ),
        new OA\Property(property: 'message', type: 'string', example: 'Reserva creada correctamente'),
    ]
)]
#[OA\Schema(
    schema: 'ReservaListResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'mis_reservas',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Reserva')
                ),
                new OA\Property(
                    property: 'reservas_de_mis_propiedades',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Reserva')
                ),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'ReservaAdminListResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'mis_reservas',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Reserva'),
                    example: []
                ),
                new OA\Property(
                    property: 'reservas_de_mis_propiedades',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Reserva'),
                    example: []
                ),
                new OA\Property(
                    property: 'todas',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Reserva')
                ),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'ReservaMutationResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(property: 'message', type: 'string', example: 'Reserva actualizada correctamente'),
    ]
)]
#[OA\Schema(
    schema: 'ReservaErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(type: 'object'),
            example: []
        ),
        new OA\Property(property: 'error', type: 'string', example: 'No tienes permiso para realizar esta operación'),
    ]
)]
#[OA\Schema(
    schema: 'ReservaValidationErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            )
        ),
        new OA\Property(property: 'error', type: 'string', example: 'Error de validación'),
    ]
)]
final class ReservaSchemas
{
}