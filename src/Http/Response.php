<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;

/**
 * Representa una respuesta HTTP de forma inmutable.
 *
 * Los métodos estáticos son fábricas de datos (status + payload + headers);
 * `emit()` es el único punto que toca el entorno real (header()/echo/exit) y
 * se llama una sola vez, en public/index.php. Los tests en cambio leen
 * `status()` y `payload()` sin emitir nada, lo que los hace puros unitarios.
 */
final class Response
{
    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly int $status = 200,
        private readonly ?array $payload = null,
        private readonly array $headers = [],
    ) {
    }

    // ===== FÁBRICAS DE ÉXITO =====

    /**
     * @param array<string, mixed>|list<mixed>|object $data
     */
    public static function success($data = [], int $status = 200, ?string $message = null): self
    {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return new self($status, $payload);
    }

    /**
     * @param array<string, mixed>|list<mixed>|object $data
     */
    public static function created($data = [], ?string $message = null): self
    {
        return self::success($data, 201, $message);
    }

    public static function noContent(): self
    {
        return new self(204);
    }

    // ===== FÁBRICAS DE ERROR =====

    /**
     * @param array<string, mixed>|null $payload
     */
    public static function json(?array $payload, int $status): self
    {
        return new self($status, $payload);
    }

    public static function validationError(array $errors): self
    {
        return new self(422, [
            'success' => false,
            'error' => 'Error de validación',
            'validation_errors' => $errors,
        ]);
    }

    public static function notFound(string $message = 'Recurso no encontrado'): self
    {
        return self::error($message, 404);
    }

    public static function methodNotAllowed(): self
    {
        return self::error('Método no permitido', 405);
    }

    public static function unauthorized(string $message = 'No autorizado'): self
    {
        return self::error($message, 401);
    }

    public static function forbidden(string $message = 'Acceso denegado'): self
    {
        return self::error($message, 403);
    }

    public static function badRequest(string $message = 'Solicitud inválida'): self
    {
        return self::error($message, 400);
    }

    public static function serverError(string $message = 'Error interno del servidor'): self
    {
        return self::error($message, 500);
    }

    public static function error(string $message, int $status): self
    {
        return new self($status, [
            'success' => false,
            'error' => $message,
        ]);
    }

    // ===== ACCESORES =====

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function payload(): ?array
    {
        return $this->payload;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Devuelve una copia con un header extra (el original no se toca).
     */
    public function withHeader(string $name, string $value): self
    {
        return new self(
            $this->status,
            $this->payload,
            $this->headers + [$name => $value]
        );
    }

    // ===== EMISIÓN (borde de la aplicación) =====

    /**
     * Escribe la respuesta al socket y termina el proceso.
     *
     * @codeCoverageIgnoreStart — sólo se ejecuta en el servidor real.
     */
    public function emit(): void
    {
        if (headers_sent() === false) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }

            if ($this->payload !== null) {
                header('Content-Type: application/json; charset=utf-8');
                header('X-Content-Type-Options: nosniff');
            }
        }

        if ($this->payload !== null) {
            try {
                echo json_encode(
                    $this->payload,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                    | JSON_THROW_ON_ERROR
                );
            } catch (JsonException) {
                http_response_code(500);

                echo json_encode([
                    'success' => false,
                    'error' => 'Error al generar JSON',
                ]);
            }
        }

        exit;
        // @codeCoverageIgnoreEnd
    }
}
