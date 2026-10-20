<?php

declare(strict_types=1);

namespace App\Http;

use App\Exceptions\BadRequestException;
use JsonException;

/**
 * Representa una petición HTTP de forma inmutable.
 *
 * Antes la capa HTTP se leía desde `$_SERVER`/`$_GET`/`php://input` de forma
 * estática, lo que obligaba a los tests a clavar estado global
 * (`Request::setTestBody()`, `$_GET['x'] = ...`). Ahora la petición es un
 * objeto que se construye una vez y se pasa a los controladores, así que un
 * test puede armarla directamente con `new Request(...)` sin tocar el entorno.
 */
final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @param array<string, string> $headers Nombres de header en notación
     *        original ("Content-Type"); el lookup es case-insensitive.
     */
    public function __construct(
        private readonly string $method = 'GET',
        private readonly string $path = '/',
        private readonly array $query = [],
        private readonly ?string $body = null,
        private readonly array $post = [],
        private readonly array $files = [],
        private readonly array $headers = [],
    ) {
    }

    /**
     * Construye la petición a partir del entorno del servidor.
     */
    public static function fromGlobals(): self
    {
        $body = file_get_contents('php://input');

        return new self(
            method: strtoupper(trim((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))),
            path: self::pathDesdeServidor(),
            query: $_GET,
            body: $body === false ? null : $body,
            post: $_POST,
            files: $_FILES,
            headers: self::headersDesdeServidor(),
        );
    }

    /**
     * Reconstruye el path relativo a la raíz del proyecto.
     *
     * Cuando la app vive en un subdirectorio (o bajo /public) el
     * `REQUEST_URI` trae ese prefijo; hay que quitarlo para que las rutas
     * registradas ("/api/...") sigan coincidiendo.
     */
    private static function pathDesdeServidor(): string
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) parse_url($requestUri, PHP_URL_PATH);

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/');
        $baseDir = str_replace('\\', '/', dirname(dirname($scriptName)));

        if ($baseDir !== '/' && str_starts_with($path, $baseDir)) {
            $path = substr($path, strlen($baseDir));
        }

        if (str_starts_with($path, '/public')) {
            $path = substr($path, strlen('/public'));
        }

        return '/' . trim($path, '/');
    }

    /**
     * Aplana los `HTTP_*` de $_SERVER a un mapa de headers.
     *
     * Apache/php-fpm a veces dejan el header de autorización únicamente en
     * REDIRECT_HTTP_AUTHORIZATION, así que se usa como respaldo.
     *
     * @return array<string, string>
     */
    private static function headersDesdeServidor(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!str_starts_with($key, 'HTTP_') || !is_string($value)) {
                continue;
            }

            $nombre = str_replace(
                ' ',
                '-',
                ucwords(strtolower(str_replace('_', ' ', substr($key, 5))))
            );

            $headers[$nombre] = $value;
        }

        if (
            !isset($headers['Authorization'])
            && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])
            && is_string($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])
        ) {
            $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return $headers;
    }

    /**
     * Devuelve una copia con otros parámetros de query.
     *
     * @param array<string, mixed> $query
     */
    public function withQuery(array $query): self
    {
        return new self(
            method: $this->method,
            path: $this->path,
            query: $query,
            body: $this->body,
            post: $this->post,
            files: $this->files,
            headers: $this->headers
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * El mapa completo de parámetros de query.
     *
     * @return array<string, mixed>
     */
    public function queryParams(): array
    {
        return $this->query;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->json()[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * El mapa completo de campos de formulario (multipart/urlencoded).
     *
     * @return array<string, mixed>
     */
    public function postParams(): array
    {
        return $this->post;
    }

    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $nombre => $valor) {
            if (strcasecmp($nombre, $name) === 0) {
                return $valor;
            }
        }

        return null;
    }

    /**
     * El cuerpo crudo de la petición, sin decodificar.
     */
    public function rawBody(): ?string
    {
        return $this->body;
    }

    /**
     * Decodifica el cuerpo JSON de la petición.
     *
     * @return array<string, mixed>
     *
     * @throws BadRequestException Si el cuerpo está vacío o no es JSON válido.
     */
    public function json(): array
    {
        $body = $this->body;

        if ($body === null || trim($body) === '') {
            throw new BadRequestException(
                'El cuerpo de la solicitud es obligatorio'
            );
        }

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestException(
                'El JSON enviado no es válido'
            );
        }

        if (!is_array($data)) {
            throw new BadRequestException(
                'El cuerpo debe ser un objeto JSON'
            );
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
