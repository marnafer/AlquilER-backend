<?php

declare (strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

Class ConflictException extends RuntimeException
{
    public function __construct(
        string $message = 'Conflicto de datos',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 409, $previous);
    }
}