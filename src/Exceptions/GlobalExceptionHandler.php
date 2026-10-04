<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Helpers\ErrorLog;
use App\Helpers\Response;
use Throwable;

class GlobalExceptionHandler
{
    public static function handle(Throwable $exception): void
    {
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

            // Solo las excepciones controladas llevan texto pensado para el
            // usuario final (validaciones, "credenciales invalidas", etc.).
            $mensaje = $exception->getMessage();
        } else {
            $status = 500;

            // Cualquier otra excepcion puede arrastar datos tecnicos sensibles
            // (SQLSTATE, nombres de tabla, rutas, credenciales). Al cliente
            // solo se le manda un mensaje generico, sin importar el entorno:
            // el detalle va al log del servidor.
            $mensaje = 'Error interno del servidor';

            // Registrar el error tanto en producción como en desarrollo, actualmente se guarda en \xampp\php\logs\php_error_log
            ErrorLog::registrar($exception);
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