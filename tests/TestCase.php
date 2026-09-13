<?php

declare(strict_types=1);

namespace Tests;

<<<<<<< HEAD
use App\Helpers\Request;
use App\Helpers\TokenProviderInterface;
use App\Middlewares\AutenticadorMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
=======
use App\Helpers\TokenProviderInterface;
use App\Middlewares\AutenticadorMiddleware;
>>>>>>> c9460ea80694538dda38eefb86136b58a78448c8
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var TokenProviderInterface&MockObject */
    protected TokenProviderInterface $tokenProvider;

    protected function setUp(): void
    {
        parent::setUp();

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

        // 2. Middleware de auth: provider por defecto que rechaza todo.
        $this->tokenProvider = $this->createMock(TokenProviderInterface::class);
        $this->tokenProvider->method('validate')->willReturn(null);
        AutenticadorMiddleware::configure($this->tokenProvider);

        // 3. Limpiar body de test por si quedó sucio de un test anterior.
        Request::setTestBody(null);
    }

    protected function tearDown(): void
    {
        Request::setTestBody(null);
        parent::tearDown();
    }

    /**
<<<<<<< HEAD
     * Configura el provider para que devuelva un usuario autenticado.
=======
     * Simula una sesión autenticada para los tests de controladores
     */
    protected function actingAs(int $id = 5, int $rolId = 1): object
    {
        $usuarioFalso = $this->createUserMock($id, $rolId);

        $tokenProvider = $this->createMock(TokenProviderInterface::class);
        $tokenProvider->method('validate')->willReturn($usuarioFalso);

        AutenticadorMiddleware::configure($tokenProvider);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token_falso_para_test';

        return $usuarioFalso;
    }

    /**
     * Crear un request mock para los controladores
>>>>>>> c9460ea80694538dda38eefb86136b58a78448c8
     */
    protected function autenticarComo(int $usuarioId = 1, int $rolId = 1): void
    {
        $this->tokenProvider
            ->method('validate')
            ->willReturn((object) [
                'sub' => $usuarioId,
                'rol_id' => $rolId,
                'email' => 'test@test.com',
            ]);
    }

    /**
     * Configura el provider para que devuelva un admin.
     * AutenticadorMiddleware::soloAdmin() exige rol_id === 2.
     */
    protected function autenticarComoAdmin(int $usuarioId = 1): void
    {
        $this->autenticarComo($usuarioId, 2);
    }

    /**
     * Inyecta un body JSON para que Request::json() lo lea durante $fn.
     */
    protected function withJsonBody(string $json, callable $fn): void
    {
        Request::setTestBody($json);
        try {
            $fn();
        } finally {
            Request::setTestBody(null);
        }
    }

    /**
     * Ejecuta $fn capturando el output y atrapando el throw
     * que Response::json lanza en APP_ENV=testing.
     */
    protected function captureResponse(callable $fn): string
    {
        ob_start();
        try {
            $fn();
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== '__RESPONSE_SENT__') {
                ob_end_clean();
                throw $e;
            }
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean() ?: '';
    }

    /**
     * Igual que captureResponse pero devuelve el JSON decodificado.
     */
    protected function captureJson(callable $fn): array
    {
        $body = $this->captureResponse($fn);
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Crea una "request" configurando el tokenProvider.
     * Si se pasa usuario_id, autentica como ese usuario.
     * Si se pasa un array vacío, no autentica.
     *
     * @param array $config Configuración con claves como 'usuario_id'
     * @return null (Solo configura el estado del tokenProvider)
     */
    protected function createRequest(array $config = []): ?object
    {
        if (!empty($config) && isset($config['usuario_id'])) {
            $usuarioId = $config['usuario_id'];
            $rolId = $config['rol_id'] ?? 1;
            $this->autenticarComo($usuarioId, $rolId);
        } else {
            // Resetear a no autenticado (rechaza todo)
            $this->tokenProvider->method('validate')->willReturn(null);
        }

        // Retornar null ya que los controladores no realmente usan el parámetro $request
        return null;
    }
}