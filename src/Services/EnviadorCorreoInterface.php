<?php

declare(strict_types=1);

namespace App\Services;

interface EnviadorCorreoInterface
{
    public function enviar(
        string $to,
        string $subject,
        string $htmlBody,
        string $textPlain,
        ?string $replyTo = null
    ): bool;
}