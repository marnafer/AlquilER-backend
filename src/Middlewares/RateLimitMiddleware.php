<?php

declare(strict_types=1);

namespace App\Middlewares;

final class RateLimitMiddleware
{
    private const WINDOW_SECONDS = 60;

    private const STORAGE_DIRECTORY = 'alquiler-backend-rate-limit';

    /**
     * Los límites se expresan en solicitudes y segundos.
     */
    private const RULES = [
        'login' => [
            'limite' => 5,
            'ventana' => 60,
        ],
        'register' => [
            'limite' => 5,
            'ventana' => 600,
        ],
        'recuperar' => [
            'limite' => 3,
            'ventana' => 300,
        ],
        'restablecer' => [
            'limite' => 5,
            'ventana' => 300,
        ],
        'refresh' => [
            'limite' => 10,
            'ventana' => 60,
        ],
        'contacto' => [
            'limite' => 10,
            'ventana' => 60,
        ],
        'crear_consulta' => [
            'limite' => 10,
            'ventana' => 60,
        ],
        'enviar_mensaje' => [
            'limite' => 20,
            'ventana' => 60,
        ],
        'admin' => [
            'limite' => 30,
            'ventana' => 60,
        ],
        'get' => [
            'limite' => 120,
            'ventana' => 60,
        ],
        'write' => [
            'limite' => 30,
            'ventana' => 60,
        ],
        'default' => [
            'limite' => 60,
            'ventana' => 60,
        ],
    ];

    public static function verificar(
        ?string $metodo = null,
        ?string $ruta = null
    ): void {
        $metodo ??= strtoupper(
            trim($_SERVER['REQUEST_METHOD'] ?? 'GET')
        );

        $ruta ??= parse_url(
            $_SERVER['REQUEST_URI'] ?? '/',
            PHP_URL_PATH
        ) ?: '/';

        // Limitar únicamente las rutas de la API.
        if (!str_starts_with($ruta, '/api/')) {
            return;
        }

        $regla = self::obtenerRegla($metodo, $ruta);

        $resultado = self::evaluarRegla(
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            time(),
            self::obtenerDirectorioAlmacenamiento(),
            $regla['nombre'],
            $regla['limite'],
            $regla['ventana']
        );

        header('X-RateLimit-Limit: ' . $regla['limite']);
        header('X-RateLimit-Remaining: ' . $resultado['restantes']);

        if (!$resultado['permitido']) {
            $retryAfter = $resultado['retry_after'];

            http_response_code(429);

            header('Content-Type: application/json; charset=utf-8');
            header('Retry-After: ' . $retryAfter);

            echo json_encode(
                [
                    'success' => false,
                    'message' => 'Demasiadas solicitudes. Intenta nuevamente más tarde.',
                    'retry_after' => $retryAfter,
                ],
                JSON_UNESCAPED_UNICODE
            );

            exit;
        }
    }

    /**
     * Se conserva la firma pública para facilitar las pruebas existentes.
     */
    public static function evaluar(
        ?string $ip = null,
        ?int $ahora = null,
        ?string $directorio = null
    ): array {
        return self::evaluarRegla(
            $ip ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            $ahora ?? time(),
            $directorio ?? self::obtenerDirectorioAlmacenamiento(),
            'general',
            self::RULES['get']['limite'],
            self::WINDOW_SECONDS
        );
    }

    private static function obtenerRegla(
        string $metodo,
        string $ruta
    ): array {
        $ruta = '/' . trim($ruta, '/');

        $reglasEspecificas = [
            'POST /api/autenticador/login' => 'login',
            'POST /api/autenticador/register' => 'register',
            'POST /api/autenticador/recuperar' => 'recuperar',
            'POST /api/autenticador/restablecer' => 'restablecer',
            'POST /api/autenticador/refresh' => 'refresh',
            'POST /api/contacto' => 'contacto',
            'POST /api/consultas' => 'crear_consulta',
        ];

        $clave = $metodo . ' ' . $ruta;

        if (isset($reglasEspecificas[$clave])) {
            $nombre = $reglasEspecificas[$clave];

            return [
                'nombre' => $nombre,
                ...self::RULES[$nombre],
            ];
        }

        if (
            $metodo === 'POST'
            && preg_match(
                '#^/api/consultas/[^/]+/mensajes$#',
                $ruta
            )
        ) {
            return [
                'nombre' => 'enviar_mensaje',
                ...self::RULES['enviar_mensaje'],
            ];
        }

        if (str_starts_with($ruta, '/api/admin/')) {
            return [
                'nombre' => 'admin',
                ...self::RULES['admin'],
            ];
        }

        if ($metodo === 'GET') {
            return [
                'nombre' => 'get',
                ...self::RULES['get'],
            ];
        }

        if (in_array($metodo, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return [
                'nombre' => 'write',
                ...self::RULES['write'],
            ];
        }

        return [
            'nombre' => 'default',
            ...self::RULES['default'],
        ];
    }

    private static function evaluarRegla(
        string $ip,
        int $ahora,
        string $directorio,
        string $nombreRegla,
        int $limite,
        int $ventana
    ): array {
        self::crearDirectorioSiNoExiste($directorio);

        if (!is_dir($directorio) || !is_writable($directorio)) {
            return self::permitirSinContador($limite);
        }

        $archivo = $directorio
            . DIRECTORY_SEPARATOR
            . hash('sha256', $ip . '|' . $nombreRegla)
            . '.json';

        $handle = @fopen($archivo, 'c+');

        if ($handle === false) {
            return self::permitirSinContador($limite);
        }

        $bloqueado = false;

        try {
            if (!flock($handle, LOCK_EX)) {
                return self::permitirSinContador($limite);
            }

            $bloqueado = true;

            rewind($handle);

            $contenido = stream_get_contents($handle);
            $datos = $contenido !== false
                ? json_decode($contenido, true)
                : null;

            if (!is_array($datos)) {
                $datos = [];
            }

            $inicio = isset($datos['window_start'])
                ? (int) $datos['window_start']
                : $ahora;

            $contador = isset($datos['count'])
                ? max(0, (int) $datos['count'])
                : 0;

            // Reiniciar también si el reloj retrocede.
            if (
                $ahora < $inicio
                || $ahora - $inicio >= $ventana
            ) {
                $inicio = $ahora;
                $contador = 0;
            }

            if ($contador >= $limite) {
                return [
                    'permitido' => false,
                    'restantes' => 0,
                    'retry_after' => max(
                        1,
                        $inicio + $ventana - $ahora
                    ),
                ];
            }

            $contador++;

            $nuevoContenido = json_encode(
                [
                    'window_start' => $inicio,
                    'count' => $contador,
                ],
                JSON_UNESCAPED_UNICODE
            );

            if ($nuevoContenido === false) {
                return self::permitirSinContador($limite);
            }

            rewind($handle);

            if (!ftruncate($handle, 0)) {
                return self::permitirSinContador($limite);
            }

            $escrito = fwrite($handle, $nuevoContenido);

            if (
                $escrito === false
                || $escrito !== strlen($nuevoContenido)
                || !fflush($handle)
            ) {
                return self::permitirSinContador($limite);
            }

            return [
                'permitido' => true,
                'restantes' => max(0, $limite - $contador),
                'retry_after' => 0,
            ];
        } finally {
            if ($bloqueado) {
                flock($handle, LOCK_UN);
            }

            fclose($handle);
        }
    }

    private static function permitirSinContador(
        int $limite
    ): array {
        return [
            'permitido' => true,
            'restantes' => $limite,
            'retry_after' => 0,
        ];
    }

    private static function obtenerDirectorioAlmacenamiento(): string
    {
        $configurado =
            $_ENV['RATE_LIMIT_STORAGE_PATH']
            ?? $_SERVER['RATE_LIMIT_STORAGE_PATH']
            ?? getenv('RATE_LIMIT_STORAGE_PATH');

        if (
            is_string($configurado)
            && trim($configurado) !== ''
        ) {
            return rtrim(
                $configurado,
                DIRECTORY_SEPARATOR
            );
        }

        return sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . self::STORAGE_DIRECTORY;
    }

    private static function crearDirectorioSiNoExiste(
        string $directorio
    ): void {
        if (is_dir($directorio)) {
            return;
        }

        if (!@mkdir($directorio, 0700, true) && !is_dir($directorio)) {
            return;
        }

        @chmod($directorio, 0700);
    }
}
