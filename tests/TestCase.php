<?php

namespace Tests;

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