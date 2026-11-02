<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/autenticador/recuperar',
    operationId: 'solicitarRecuperacionContrasena',
    summary: 'Solicitar recuperación de contraseña',
    description: 'Solicita el envío de un enlace para restablecer la contraseña. Por seguridad, la respuesta es la misma tanto si el correo existe como si no está registrado. El enlace tiene una vigencia de 60 minutos.',
    tags: ['Autenticación'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/RecuperarContrasenaSolicitudRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Solicitud procesada. No se revela si el correo está registrado.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/RecuperarContrasenaSuccessResponse',
                example: [
                    'success' => true,
                    'data' => [],
                    'message' => 'Si el correo existe, recibirás un enlace para restablecer tu contraseña',
                ]
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Error de validación del correo electrónico.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/RecuperarContrasenaValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Post(
    path: '/api/autenticador/restablecer',
    operationId: 'restablecerContrasena',
    summary: 'Restablecer contraseña',
    description: 'Restablece la contraseña utilizando el correo electrónico y el token recibido. El token debe ser válido, no haber sido utilizado y encontrarse dentro de su período de vigencia de 60 minutos.',
    tags: ['Autenticación'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/RecuperarContrasenaRestablecerRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Contraseña restablecida correctamente.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/RecuperarContrasenaSuccessResponse',
                example: [
                    'success' => true,
                    'data' => [],
                    'message' => 'Contraseña restablecida correctamente',
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Token inválido, vencido o ya utilizado, o usuario inexistente.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/RecuperarContrasenaErrorResponse'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Error de validación de los datos enviados.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/RecuperarContrasenaValidationErrorResponse'
            )
        ),
    ]
)]
#[OA\Post(
    path: '/api/autenticador/confirmar-cambio',
    operationId: 'confirmarCambioContrasena',
    summary: 'Confirmar cambio de contraseña',
    description: 'Confirma el cambio de contraseña solicitado desde el perfil mediante el token enviado por correo electrónico. El token tiene una vigencia de 60 minutos.',
    tags: ['Autenticación'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/RecuperarContrasenaConfirmarCambioRequest'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Cambio de contraseña confirmado.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/RecuperarContrasenaSuccessResponse',
                example: [
                    'success' => true,
                    'data' => [],
                    'message' => 'Contraseña actualizada correctamente',
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Token inválido, vencido o ya utilizado, o solicitud de cambio inexistente.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/RecuperarContrasenaErrorResponse'
            )
        ),
    ]
)]
final class RecuperarContrasenaDocs
{
}
