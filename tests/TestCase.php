<?php

declare(strict_types=1);

namespace Tests;

use App\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base de los tests del proyecto.
 *
 * Solo aísla el entorno y ofrece helpers para construir `Request`s. Ya no
 * hay estado global que clavar: los controladores reciben la petición por
 * parámetro y devuelven un `Response`, así que un test simplemente construye
 * el objeto y lee el resultado.
 */
abstract class TestCase extends BaseTestCase
{
    /** @var TokenProviderInterface&MockObject */
    protected \App\Helpers\TokenProviderInterface $tokenProvider;

    protected function setUp(): void
    {
        parent::setUp();

        // 0. Aislar superglobals entre tests.
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        // 1. Constantes que JwtHelper espera.
        //    No incluimos config/config.php para no disparar la
        //    validación de $_ENV (que en CLI está vacío).
        if (!defined('JWT_KEY')) {
            define('JWT_KEY', str_repeat('a', 32));
        }
        if (!defined('JWT_EXPIRATION')) {
            define('JWT_EXPIRATION', 3600);
        }
        if (!defined('JWT_ALGORITHM')) {
            define('JWT_ALGORITHM', 'HS256');
        }
        if (!defined('APP_ENV')) {
            define('APP_ENV', 'testing');
        }

        $this->tokenProvider = $this->createMock(
            \App\Helpers\TokenProviderInterface::class
        );
        $this->tokenProvider->method('validate')->willReturn(null);
    }

    /**
     * Usuario falso autenticado, como lo devuelve JwtProvider::validate().
     *
     * @return object{sub:int, rol_id:int, email:string}
     */
    protected function usuarioFalso(int $id = 5, int $rolId = 1): object
    {
        return (object) [
            'sub' => $id,
            'rol_id' => $rolId,
            'email' => 'test@test.com',
        ];
    }

    /**
     * Un Authenticator cuyo provider devuelve el usuario dado.
     *
     * Si no se pasa usuario, el provider rechaza todo (no autenticado).
     */
    protected function authenticator(
        ?object $usuario = null
    ): \App\Security\Authenticator {
        $provider = $this->createMock(
            \App\Helpers\TokenProviderInterface::class
        );

        $provider
            ->method('validate')
            ->willReturn($usuario);

        return new \App\Security\Authenticator($provider);
    }

    /**
     * Construye un Request a partir de un array plano.
     *
     * Acepta: method, path, query, body (array → JSON), post, files,
     * headers y rawBody (string crudo, para probar JSON malformado).
     *
     * Por defecto agrega un header Authorization para que el Authenticator
     * inyectado en el test acepte la petición. Usa `'auth' => false` para
     * simular un visitante sin sesión.
     */
    protected function makeRequest(array $config = []): Request
    {
        $body = null;

        if (array_key_exists('rawBody', $config)) {
            $body = $config['rawBody'];
        } elseif (isset($config['body'])) {
            $body = is_string($config['body'])
                ? $config['body']
                : json_encode($config['body']);
        }

        $headers = $config['headers'] ?? [];

        if (($config['auth'] ?? true) !== false) {
            $headers['Authorization'] = 'Bearer token_falso_para_test';
        }

        return new Request(
            method: $config['method'] ?? 'GET',
            path: $config['path'] ?? '/',
            query: $config['query'] ?? [],
            body: $body,
            post: $config['post'] ?? [],
            files: $config['files'] ?? [],
            headers: $headers,
        );
    }

    /**
     * Un Request con un cuerpo JSON y (por defecto) un token de sesión.
     */
    protected function makeJsonRequest(
        array $body,
        array $extra = []
    ): Request {
        return $this->makeRequest(array_merge([
            'method' => 'POST',
            'body' => $body,
        ], $extra));
    }
}
