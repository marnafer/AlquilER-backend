<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class MethodNotAllowedException extends RuntimeException
{
    public function __construct(
        string $message = 'Método no permitido',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 405, $previous);
    }
}