<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Helpers\Response;
use Throwable;

class GlobalExceptionHandler
{
    public static function handle(Throwable $exception): void
    {
        $debug = filter_var(
            $_ENV['APP_DEBUG'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $esExcepcionControlada =
            $exception instanceof BadRequestException
            || $exception instanceof UnauthorizedException
            || $exception instanceof ForbiddenException
            || $exception instanceof NotFoundException
            || $exception instanceof MethodNotAllowedException
            || $exception instanceof ConflictException
            || $exception instanceof ValidationException;

        if ($esExcepcionControlada) {
            $status = (int) $exception->getCode();

            if ($status < 400 || $status > 499) {
                $status = 500;
            }

            $mensaje = $exception->getMessage();
        } else {
            $status = 500;
            $mensaje = $debug
                ? $exception->getMessage()
                : 'Error interno del servidor';

            if (!$debug) {
                error_log($exception->__toString());
            }
        }

        $response = [
            'success' => false,
            'error' => $mensaje,
        ];

        if ($exception instanceof ValidationException) {
            $response['validation_errors'] = $exception->errors();
        }

        Response::json($response, $status);
    }
}