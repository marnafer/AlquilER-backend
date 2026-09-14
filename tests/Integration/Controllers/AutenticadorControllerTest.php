<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\AutenticadorController;
use App\Services\AutenticadorService;
use App\Helpers\Request;
use App\Helpers\TokenProviderInterface;
use App\Middlewares\AutenticadorMiddleware;
use App\Models\Usuario;

class AutenticadorControllerTest extends TestCase
{
    private $controller;
    private $service;
    private $tokenProviderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(
            AutenticadorService::class
        );

        $this->controller = new AutenticadorController(
            $this->service
        );

        $this->tokenProviderMock = $this->createMock(
            TokenProviderInterface::class
        );

        $this->tokenProviderMock
            ->method('validate')
            ->willReturn(
                (object) [
                    'sub' => 1,
                    'rol_id' => 1
                ]
            );

        AutenticadorMiddleware::configure(
            $this->tokenProviderMock
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_jwt_valido';
    }

    protected function tearDown(): void
    {
        Request::setTestBody(null);

        unset(
            $_SERVER['HTTP_AUTHORIZATION']
        );

        parent::tearDown();
    }

    public function test_el_controlador_puede_iniciar_sesion(): void
    {
        Request::setTestBody(
            json_encode([
                'email' => 'test@test.com',
                'contrasena' => 'password123'
            ])
        );

        $resultado = [
            'access_token' => 'jwt_token_valido',
            'refresh_token' => 'refresh_token_valido',
            'rol_id' => 1
        ];

        $this->service
            ->expects($this->once())
            ->method('login')
            ->with([
                'email' => 'test@test.com',
                'contrasena' => 'password123'
            ])
            ->willReturn($resultado);

        ob_start();

        try {
            $this->controller->login();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            'jwt_token_valido',
            $response['data']['access_token']
        );

        $this->assertSame(
            'refresh_token_valido',
            $response['data']['refresh_token']
        );

        $this->assertSame(
            1,
            $response['data']['rol_id']
        );

        $this->assertSame(
            200,
            http_response_code()
        );
    }

    public function test_el_controlador_procesa_un_error_de_validacion_al_iniciar_sesion(): void
    {
        Request::setTestBody(
            json_encode([
                'email' => 'test@test.com'
            ])
        );

        $this->service
            ->expects($this->once())
            ->method('login')
            ->with([
                'email' => 'test@test.com'
            ])
            ->willThrowException(
                new \App\Exceptions\ValidationException([
                    'credenciales' => [
                        'Faltan datos'
                    ]
                ])
            );

        $this->expectException(
            \App\Exceptions\ValidationException::class
        );

        $this->controller->login();
    }

    public function test_el_controlador_puede_registrar_un_usuario(): void
    {
        Request::setTestBody(
            json_encode([
                'nombre' => 'Test',
                'apellido' => 'User',
                'email' => 'test@test.com',
                'contrasena' => 'password123',
                'telefono' => '123456789',
                'domicilio' => 'Calle 123'
            ])
        );

        $usuario = new Usuario();
        $usuario->id = 1;

        $this->service
            ->expects($this->once())
            ->method('registrar')
            ->with([
                'nombre' => 'Test',
                'apellido' => 'User',
                'email' => 'test@test.com',
                'contrasena' => 'password123',
                'telefono' => '123456789',
                'domicilio' => 'Calle 123'
            ])
            ->willReturn($usuario);

        ob_start();

        try {
            $this->controller->register();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            'Usuario registrado',
            $response['message']
        );

        $this->assertSame(
            201,
            http_response_code()
        );
    }

    public function test_el_controlador_puede_actualizar_el_token(): void
    {
        Request::setTestBody(
            json_encode([
                'refresh_token' => 'token_viejo_enviado_por_cliente'
            ])
        );

        $resultado = [
            'access_token' => 'nuevo_jwt_token',
            'refresh_token' => 'nuevo_refresh_token',
            'rol_id' => 1
        ];

        $this->service
            ->expects($this->once())
            ->method('refresh')
            ->with([
                'refresh_token' => 'token_viejo_enviado_por_cliente'
            ])
            ->willReturn($resultado);

        ob_start();

        try {
            $this->controller->refresh();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            'nuevo_jwt_token',
            $response['data']['access_token']
        );

        $this->assertSame(
            'nuevo_refresh_token',
            $response['data']['refresh_token']
        );

        $this->assertSame(
            1,
            $response['data']['rol_id']
        );

        $this->assertSame(
            200,
            http_response_code()
        );
    }

    public function test_el_controlador_puede_cerrar_la_sesion(): void
    {
        $this->tokenProviderMock
            ->expects($this->once())
            ->method('validate')
            ->with('token_jwt_valido')
            ->willReturn(
                (object) [
                    'sub' => 1,
                    'rol_id' => 1
                ]
            );

        ob_start();

        try {
            $this->controller->logout();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            'Logout (el cliente elimina el token)',
            $response['message']
        );

        $this->assertSame(
            200,
            http_response_code()
        );
    }
}