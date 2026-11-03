<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ResenaUsuario',
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
    schema: 'ResenaImagen',
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
    schema: 'ResenaPropiedad',
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
        new OA\Property(property: 'requisitos_interesados', type: 'object', nullable: true, example: null),
        new OA\Property(property: 'disponible', type: 'boolean', example: false),
        new OA\Property(property: 'destacada', type: 'boolean', example: false),
        new OA\Property(property: 'categoria_id', type: 'integer', example: 1),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 2),
        new OA\Property(property: 'localidad_id', type: 'integer', example: 1),
        new OA\Property(property: 'fecha_publicacion', type: 'string', format: 'date-time', nullable: true, example: '2026-10-28T17:11:36.000000Z'),
        new OA\Property(property: 'imagen_url', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'imagen_principal', type: 'string', nullable: true, example: null),
        new OA\Property(
            property: 'imagenes',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ResenaImagen')
        ),
    ]
)]
#[OA\Schema(
    schema: 'ResenaReserva',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 5),
        new OA\Property(property: 'propiedad_id', type: 'integer', example: 1),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 2),
        new OA\Property(property: 'fecha_inicio_alquiler', type: 'string', format: 'date', example: '2026-05-01'),
        new OA\Property(property: 'fecha_fin_alquiler', type: 'string', format: 'date', nullable: true, example: null),
        new OA\Property(property: 'estado', type: 'string', example: 'finalizada'),
        new OA\Property(property: 'fecha_reserva', type: 'string', format: 'date-time', nullable: true, example: '2026-06-15T03:51:20.000000Z'),
        new OA\Property(property: 'fecha_confirmacion', type: 'string', format: 'date-time', nullable: true, example: null),
        new OA\Property(
            property: 'propiedad',
            ref: '#/components/schemas/ResenaPropiedad',
            nullable: true
        ),
        new OA\Property(
            property: 'usuario',
            ref: '#/components/schemas/ResenaUsuario',
            nullable: true
        ),
    ]
)]
#[OA\Schema(
    schema: 'Resena',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'reserva_id', type: 'integer', example: 5),
        new OA\Property(property: 'tipo', type: 'string', enum: ['propiedad', 'inquilino'], example: 'inquilino'),
        new OA\Property(property: 'calificador_id', type: 'integer', example: 2),
        new OA\Property(property: 'calificacion', type: 'integer', minimum: 1, maximum: 5, example: 5),
        new OA\Property(property: 'comentario', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'fecha_publicacion', type: 'string', format: 'date-time', example: '2026-11-03T22:29:18.000000Z'),
        new OA\Property(
            property: 'reserva',
            ref: '#/components/schemas/ResenaReserva',
            nullable: true
        ),
        new OA\Property(
            property: 'calificador',
            ref: '#/components/schemas/ResenaUsuario',
            nullable: true
        ),
    ]
)]
#[OA\Schema(
    schema: 'ResenaCreateRequest',
    type: 'object',
    required: ['reserva_id', 'calificacion'],
    properties: [
        new OA\Property(property: 'reserva_id', type: 'integer', minimum: 1, example: 5),
        new OA\Property(property: 'calificacion', type: 'integer', minimum: 1, maximum: 5, example: 5),
        new OA\Property(property: 'comentario', type: 'string', nullable: true, minLength: 3, maxLength: 1000, example: 'Muy buena experiencia'),
    ]
)]
#[OA\Schema(
    schema: 'ResenaUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'calificacion', type: 'integer', minimum: 1, maximum: 5, example: 4),
        new OA\Property(property: 'comentario', type: 'string', nullable: true, minLength: 3, maxLength: 1000, example: 'Experiencia satisfactoria'),
    ]
)]
#[OA\Schema(
    schema: 'ResenaResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/Resena'
        ),
        new OA\Property(property: 'message', type: 'string', example: 'Reseña encontrada'),
    ]
)]
#[OA\Schema(
    schema: 'ResenaListResponse',
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
                    items: new OA\Items(ref: '#/components/schemas/Resena')
                ),
                new OA\Property(property: 'total', type: 'integer', example: 2),
            ]
        ),
        new OA\Property(property: 'message', type: 'string', example: 'Reseñas obtenidas correctamente'),
    ]
)]
#[OA\Schema(
    schema: 'ResenaCollectionResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Resena')
        ),
        new OA\Property(property: 'message', type: 'string', example: 'Reseñas obtenidas correctamente'),
    ]
)]
#[OA\Schema(
    schema: 'ResenaPromedioResponse',
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
                    items: new OA\Items(ref: '#/components/schemas/Resena')
                ),
                new OA\Property(property: 'total', type: 'integer', example: 2),
                new OA\Property(property: 'promedio', type: 'number', format: 'float', example: 4.5),
            ]
        ),
        new OA\Property(property: 'message', type: 'string', example: 'Reseñas obtenidas correctamente'),
    ]
)]
#[OA\Schema(
    schema: 'ResenaMutationResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string'), example: []),
        new OA\Property(property: 'message', type: 'string', example: 'Reseña eliminada correctamente'),
    ]
)]
#[OA\Schema(
    schema: 'ResenaNullResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'message', type: 'string', example: 'Reseña actualizada correctamente'),
    ]
)]
#[OA\Schema(
    schema: 'ResenaErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'error', type: 'string', example: 'No se encontró la reseña'),
    ]
)]
#[OA\Schema(
    schema: 'ResenaValidationErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'error', type: 'string', example: 'Error de validación'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
            example: ['calificacion' => ['La calificación debe estar entre 1 y 5']]
        ),
    ]
)]
final class ResenaSchemas
{
}
