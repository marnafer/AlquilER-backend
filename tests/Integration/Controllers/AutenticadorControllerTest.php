<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\AutenticadorController;
use App\Services\AutenticadorService;
use App\Exceptions\ValidationException;
use App\Exceptions\UnauthorizedException;
use App\Helpers\Request;
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
            'contrasena' => 'password123'
        ]);

        // Ahora esperamos que el servicio devuelva la estructura con ambos tokens
        $this->service
            ->expects($this->once())
            ->method('login')
            ->willReturn([
                'access_token' => 'jwt_token_valido', 
                'refresh_token' => 'refresh_token_valido',
                'rol_id' => 1
            ]);

        $output = $this->captureResponse(function () use ($input) {
            Request::setTestBody($input);
            $this->controller->login();
        });

        $this->assertStringContainsString('"success": true', $output);
        $this->assertStringContainsString('"access_token"', $output);
        $this->assertStringContainsString('"refresh_token"', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_login_missing_fields()
    {
        $input = json_encode(['email' => 'test@test.com']);

        // Simulamos que el servicio lanza la excepción de validación porque falta la contraseña
        $this->service
            ->expects($this->once())
            ->method('login')
            ->willThrowException(new ValidationException(['credenciales' => ['Faltan datos']]));

        $this->expectException(ValidationException::class);
        
        Request::setTestBody($input);
        $this->controller->login();
    }

    /** @test */
    public function it_can_register()
    {
        $input = json_encode([
            'nombre' => 'Test',
            'apellido' => 'User',
            'email' => 'test@test.com',
            'contrasena' => 'password123',
            'telefono' => '123456789',
            'domicilio' => 'Calle 123'
        ]);

        // Tu servicio registrar ahora devuelve un objeto Usuario
        $usuarioMock = new Usuario(['id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('registrar')
            ->willReturn($usuarioMock);

        $output = $this->captureResponse(function () use ($input) {
            Request::setTestBody($input);
            $this->controller->register();
        });

        $this->assertStringContainsString('"success": true', $output);
        $this->assertStringContainsString('Usuario registrado', $output);
    }

    /** @test */
    public function it_can_refresh_token()
    {
        $input = json_encode([
            'refresh_token' => 'token_viejo_enviado_por_cliente'
        ]);

        $this->service
            ->expects($this->once())
            ->method('refresh')
            ->willReturn([
                'access_token' => 'nuevo_jwt_token', 
                'refresh_token' => 'nuevo_refresh_token',
                'rol_id' => 1
            ]);

        $output = $this->captureResponse(function () use ($input) {
            Request::setTestBody($input);
            $this->controller->refresh();
        });

        $this->assertStringContainsString('"success": true', $output);
        $this->assertStringContainsString('"nuevo_jwt_token"', $output);
        $this->assertStringContainsString('"nuevo_refresh_token"', $output);
    }

    /** @test */
    public function it_can_logout()
    {
        // Simulamos el payload del frontend enviando el refresh token
        $input = json_encode(['refresh_token' => 'token_a_invalidar']);

        // Simulamos el header de autorización que exige AutenticadorMiddleware::verificar()
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token_jwt_valido';

        // Verificamos que el controlador llame al servicio pasándole el JSON
        $this->service
            ->expects($this->once())
            ->method('logout')
            ->with($this->callback(function ($data) {
                return isset($data['refresh_token']) && $data['refresh_token'] === 'token_a_invalidar';
            }));

        $output = $this->captureResponse(function () use ($input) {
            Request::setTestBody($input);
            $this->controller->logout();
        });

        $this->assertStringContainsString('"success": true', $output);
        $this->assertStringContainsString('Sesión cerrada correctamente', $output);
    }
}