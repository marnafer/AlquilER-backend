<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class ForbiddenException extends RuntimeException
{
    public function __construct(
        string $message = 'Acceso denegado',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 403, $previous);
    }
}