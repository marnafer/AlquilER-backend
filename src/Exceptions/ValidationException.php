<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    private readonly array $errors;

    public function __construct(
        array $errors,
        string $message = 'Error de validación'
    ) {
        $this->errors = array_map(
            static fn ($error): array =>
                is_array($error) ? $error : [$error],
            $errors
        );

        parent::__construct($message, 422);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}