<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class UnauthorizedException extends RuntimeException
{
    public function __construct(
        string $message = 'No autorizado',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 401, $previous);
    }
}