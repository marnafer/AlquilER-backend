<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Contacto',
    description: 'Envío de mensajes al equipo de AlquilER'
)]

final class ContactoDocs
{
    #[OA\Post(
        path: '/api/contacto',
        operationId: 'enviarMensajeContacto',
        summary: 'Enviar un mensaje de contacto',
        description: 'Permite enviar un mensaje al equipo de AlquilER. No requiere autenticación.',
        tags: ['Contacto'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/ContactoRequest'
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Mensaje enviado correctamente',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ContactoSuccessResponse'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Solicitud incorrecta o servicio de contacto no configurado',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ContactoErrorResponse'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación de los datos enviados',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ContactoValidationErrorResponse'
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error interno del servidor',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ContactoErrorResponse'
                )
            ),
        ]
    )]
    public function store(): void
    {
    }
}
