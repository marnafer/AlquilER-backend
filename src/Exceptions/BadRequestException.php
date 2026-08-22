<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class BadRequestException extends RuntimeException
{
    public function __construct(
        string $message = 'Solicitud inválida',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 400, $previous);
    }
}