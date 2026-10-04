<?php

declare(strict_types=1);

namespace App\Helpers;

use Throwable;

/**
 * Registro de errores no controlados.
 *
 * La respuesta que ve el usuario final nunca debe incluir datos tecnicos
 * (SQLSTATE, nombres de tabla, rutas del servidor). Ese detalle queda aca,
 * en el log del backend, con fecha, origen y stack trace.
 */
class ErrorLog
{
    /**
     * Tamano maximo antes de rotar el archivo.
     */
    private const MAX_BYTES = 5 * 1024 * 1024;

    public static function ruta(): string
    {
        return dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'errores.log';
    }

    /**
     * Guarda el detalle tecnico de la excepcion.
     *
     * Este metodo se invoca desde el manejador global de errores, asi que
     * jamas debe lanzar: un fallo al escribir el log no puede convertirse
     * en un error 500 sin respuesta para el cliente.
     */
    public static function registrar(Throwable $exception): void
    {
        try {
            $archivo = self::ruta();
            $directorio = dirname($archivo);

            if (!is_dir($directorio)) {
                @mkdir($directorio, 0777, true);
            }

            self::rotarSiCorresponde($archivo);

            $linea = sprintf(
                "[%s] %s: %s%s    origen: %s:%d%s    traza: %s%s%s",
                date('Y-m-d H:i:s'),
                get_class($exception),
                $exception->getMessage(),
                PHP_EOL,
                $exception->getFile(),
                $exception->getLine(),
                PHP_EOL,
                $exception->getTraceAsString(),
                PHP_EOL,
                str_repeat('-', 100)
            );

            @file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX);

            // Se mantiene tambien el log del servidor (Apache lo captura en
            // el error_log de php.ini) para no perder nada si el archivo no
            // se puede escribir.
            error_log($exception->__toString());
        } catch (Throwable $falloAlRegistrar) {
            // Silencio intencional: ver la nota de este metodo.
        }
    }

    /**
     * Conserva una sola generacion anterior para que el log no crezca sin
     * limite si una ruta empieza a fallar en repetidas peticiones.
     */
    private static function rotarSiCorresponde(string $archivo): void
    {
        if (!is_file($archivo)) {
            return;
        }

        $tamano = @filesize($archivo);

        if ($tamano === false || $tamano < self::MAX_BYTES) {
            return;
        }

        @rename($archivo, $archivo . '.1');
    }
}