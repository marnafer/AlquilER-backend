<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    public function __construct(
        private readonly array $errors,
        string $message = 'Error de validación'
    ) {
        parent::__construct($message, 422);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}