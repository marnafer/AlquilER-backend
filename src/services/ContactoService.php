<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ValidationException;
use App\Sanitizers\ContactoSanitizer;
use App\Validators\ContactoValidator;

class ContactoService
{
    public function __construct(
        private readonly MailService $mailService,
        private readonly string $destino
    ) {
    }

    /**
     * Envía un mensaje de contacto al correo configurado.
     */
    public function enviar(array $rawData): void
    {
        $data = ContactoSanitizer::sanitizar($rawData);

        $validacion = ContactoValidator::validar($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $destino = trim($this->destino);

        if ($destino === '') {
            throw new BadRequestException(
                'El servicio de contacto no está configurado'
            );
        }

        $cuerpo = '<p><strong>' . htmlspecialchars($data['nombre'], ENT_QUOTES, 'UTF-8') . '</strong>'
            . ' (&lt;' . htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8') . '&gt;) escribió:</p>'
            . '<p>' . nl2br(htmlspecialchars($data['mensaje'], ENT_QUOTES, 'UTF-8')) . '</p>';

        $texto = $data['nombre']
            . ' (' . $data['email'] . ') escribió:' . "\n\n"
            . $data['mensaje'];

        $this->mailService->enviar(
            $destino,
            'Contacto - ' . $data['asunto'],
            $cuerpo,
            $texto,
            $data['email']
        );
    }
}