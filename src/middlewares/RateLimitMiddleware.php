<?php

declare(strict_types=1);

namespace App\Middlewares;

final class RateLimitMiddleware
{
    private const MAX_REQUESTS = 60;

    private const WINDOW_SECONDS = 60;

    private const STORAGE_DIRECTORY =
        'alquiler-backend-rate-limit';

    public static function verificar(): void
    {
        $resultado = self::evaluar();

        if ($resultado['permitido']) {
            return;
        }

        $retryAfter = $resultado['retry_after'];

        http_response_code(429);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        header(
            'Retry-After: ' . $retryAfter
        );

        header(
            'X-RateLimit-Limit: ' .
            self::MAX_REQUESTS
        );

        header(
            'X-RateLimit-Remaining: 0'
        );

        echo json_encode(
            [
                'success' => false,
                'message' =>
                    'Demasiadas solicitudes. Intenta nuevamente más tarde.',
                'retry_after' => $retryAfter,
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    public static function evaluar(
        ?string $ip = null,
        ?int $ahora = null
    ): array {
        $ip ??=
            $_SERVER['REMOTE_ADDR']
            ?? 'unknown';

        $ahora ??= time();

        $directorio =
            self::obtenerDirectorioAlmacenamiento();

        self::crearDirectorioSiNoExiste(
            $directorio
        );

        $archivo =
            $directorio .
            DIRECTORY_SEPARATOR .
            hash(
                'sha256',
                $ip
            ) .
            '.json';

        $handle = fopen($archivo, 'c+');

        if ($handle === false) {
            /*
             * Si el almacenamiento falla,
             * no bloqueamos la API.
             */
            return [
                'permitido' => true,
                'restantes' =>
                    self::MAX_REQUESTS,
                'retry_after' => 0,
            ];
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return [
                    'permitido' => true,
                    'restantes' =>
                        self::MAX_REQUESTS,
                    'retry_after' => 0,
                ];
            }

            rewind($handle);

            $contenido =
                stream_get_contents($handle);

            $datos = [];

            if (
                $contenido !== false &&
                trim($contenido) !== ''
            ) {
                $decodificado =
                    json_decode(
                        $contenido,
                        true
                    );

                if (is_array($decodificado)) {
                    $datos = $decodificado;
                }
            }

            $inicioVentana =
                isset($datos['window_start'])
                    ? (int) $datos['window_start']
                    : $ahora;

            $contador =
                isset($datos['count'])
                    ? (int) $datos['count']
                    : 0;

            if (
                $ahora - $inicioVentana >=
                self::WINDOW_SECONDS
            ) {
                $inicioVentana = $ahora;
                $contador = 0;
            }

            if (
                $contador >=
                self::MAX_REQUESTS
            ) {
                $retryAfter = max(
                    1,
                    self::WINDOW_SECONDS -
                    (
                        $ahora -
                        $inicioVentana
                    )
                );

                return [
                    'permitido' => false,
                    'restantes' => 0,
                    'retry_after' =>
                        $retryAfter,
                ];
            }

            $contador++;

            $datos = [
                'window_start' =>
                    $inicioVentana,
                'count' => $contador,
            ];

            rewind($handle);

            ftruncate($handle, 0);

            fwrite(
                $handle,
                json_encode($datos)
            );

            fflush($handle);

            $restantes = max(
                0,
                self::MAX_REQUESTS -
                $contador
            );

            return [
                'permitido' => true,
                'restantes' => $restantes,
                'retry_after' => 0,
            ];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private static function
    obtenerDirectorioAlmacenamiento(): string
    {
        return
            sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            self::STORAGE_DIRECTORY;
    }

    private static function
    crearDirectorioSiNoExiste(
        string $directorio
    ): void {
        if (
            is_dir($directorio)
        ) {
            return;
        }

        @mkdir(
            $directorio,
            0777,
            true
        );
    }
}