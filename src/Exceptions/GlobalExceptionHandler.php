<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Helpers\Response;
use App\Exceptions\ValidationException;
use Throwable;

class GlobalExceptionHandler
{
    public static function handle(Throwable $exception): void
    {
        $status = $exception->getCode();

        if ($status < 400 || $status > 599) {
            $status = 500;
        }

        $response = [
            'success' => false,
            'error' => $exception->getMessage(),
        ];

        if ($exception instanceof ValidationException) {
            $response['validation_errors'] = $exception->errors();
        }

        Response::json($response, $status);
    }
}