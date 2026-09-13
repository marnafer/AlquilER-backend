<?php

namespace Tests;

use App\Helpers\TokenProviderInterface;
use App\Middlewares\AutenticadorMiddleware;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Configuración base para todos los tests
    protected function setUp(): void
    {
        parent::setUp();
        // Aquí puedes cargar configuración de base de datos de prueba si es necesario
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Limpiar después de cada test si es necesario
    }

    /**
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
     */
    protected function createRequest(array $data = [], array $headers = []): object
    {
        return (object) [
            'usuario_id' => $data['usuario_id'] ?? null,
            'headers' => $headers,
            'body' => $data
        ];
    }

    /**
     * Crear un usuario mock para pruebas
     */
    protected function createUserMock(int $id = 1, int $rolId = 2): object
    {
        return (object) [
            'sub' => $id,
            'rol_id' => $rolId,
            'id' => $id,
            'nombre' => 'Usuario Test',
            'email' => 'test@test.com'
        ];
    }
}