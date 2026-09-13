<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\AutenticadorController;
use App\Services\AutenticadorService;
use App\Exceptions\ValidationException;
use App\Exceptions\UnauthorizedException;
use App\Models\Usuario;

class AutenticadorControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createMock(AutenticadorService::class);
        $this->controller = new AutenticadorController($this->service);
    }

    /** @test */
    public function it_can_login()
    {
        $input = json_encode([
            'email' => 'test@test.com',
            'contrasena' => 'password123' // Corregido de password a contrasena según tu service
        ]);
        file_put_contents('php://input', $input);

        // Ahora esperamos que el servicio devuelva la estructura con ambos tokens
        $this->service
            ->expects($this->once())
            ->method('login')
            ->willReturn([
                'access_token' => 'jwt_token_valido', 
                'refresh_token' => 'refresh_token_valido',
                'rol_id' => 1
            ]);

        ob_start();
        $this->controller->login();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('"access_token"', $output);
        $this->assertStringContainsString('"refresh_token"', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_login_missing_fields()
    {
        $input = json_encode(['email' => 'test@test.com']);
        file_put_contents('php://input', $input);

        // Simulamos que el servicio lanza la excepción de validación porque falta la contraseña
        $this->service
            ->expects($this->once())
            ->method('login')
            ->willThrowException(new ValidationException(['credenciales' => ['Faltan datos']]));

        $this->expectException(ValidationException::class);
        
        $this->controller->login();
    }

    /** @test */
    public function it_can_register() // Cambiado a register() para coincidir con tu controlador
    {
        $input = json_encode([
            'nombre' => 'Test',
            'apellido' => 'User',
            'email' => 'test@test.com',
            'contrasena' => 'password123',
            'telefono' => '123456789',
            'domicilio' => 'Calle 123'
        ]);
        file_put_contents('php://input', $input);

        // Tu servicio registrar ahora devuelve un objeto Usuario
        $usuarioMock = new Usuario(['id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('registrar')
            ->willReturn($usuarioMock);

        ob_start();
        $this->controller->register();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('Usuario registrado', $output);
    }

    /** @test */
    public function it_can_refresh_token()
    {
        $input = json_encode([
            'refresh_token' => 'token_viejo_enviado_por_cliente'
        ]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('refresh')
            ->willReturn([
                'access_token' => 'nuevo_jwt_token', 
                'refresh_token' => 'nuevo_refresh_token',
                'rol_id' => 1
            ]);

        ob_start();
        $this->controller->refresh();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('"nuevo_jwt_token"', $output);
        $this->assertStringContainsString('"nuevo_refresh_token"', $output);
    }

    /** @test */
    public function it_can_logout()
    {
        // Simulamos el payload del frontend enviando el refresh token
        $input = json_encode(['refresh_token' => 'token_a_invalidar']);
        file_put_contents('php://input', $input);

        // Simulamos el header de autorización que exige AutenticadorMiddleware::verificar()
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token_jwt_valido';

        // Verificamos que el controlador llame al servicio pasándole el JSON
        $this->service
            ->expects($this->once())
            ->method('logout')
            ->with($this->callback(function ($data) {
                return isset($data['refresh_token']) && $data['refresh_token'] === 'token_a_invalidar';
            }));

        ob_start();
        $this->controller->logout();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('Sesión cerrada correctamente', $output);
    }
}