<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\AutenticadorController;
use App\Services\AutenticadorService;
use App\Exceptions\ValidationException;
use App\Exceptions\UnauthorizedException;

class AutenticadorControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(
            AutenticadorService::class
        );

        $this->controller = new AutenticadorController(
            $this->service
        );
    }

    public function test_login_funciona_correctamente(): void
    {
        $resultado = [
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ];

        $this->service
            ->expects($this->once())
            ->method('login')
            ->with([
                'email' => 'test@test.com',
                'contrasena' => 'password123',
            ])
            ->willReturn($resultado);

        $response = $this->captureJsonWithBody(
            json_encode([
                'email' => 'test@test.com',
                'contrasena' => 'password123',
            ]),
            fn() => $this->controller->login()
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            $resultado,
            $response['data']
        );
    }

    public function test_login_devuelve_error_de_validacion(): void
    {
        $this->service
            ->expects($this->once())
            ->method('login')
            ->with([
                'email' => 'test@test.com',
            ])
            ->willThrowException(
                new ValidationException([
                    'credenciales' => [
                        'Faltan datos',
                    ],
                ])
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'email' => 'test@test.com',
            ]),
            fn() => $this->controller->login()
        );

        $this->assertFalse($response['success']);

        $this->assertArrayHasKey(
            'validation_errors',
            $response
        );

        $this->assertSame(
            [
                'credenciales' => [
                    'Faltan datos',
                ],
            ],
            $response['validation_errors']
        );
    }

    public function test_login_devuelve_error_si_las_credenciales_son_invalidas(): void
    {
        $this->service
            ->expects($this->once())
            ->method('login')
            ->with([
                'email' => 'test@test.com',
                'contrasena' => 'password_incorrecta',
            ])
            ->willThrowException(
                new UnauthorizedException(
                    'Credenciales inválidas'
                )
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'email' => 'test@test.com',
                'contrasena' => 'password_incorrecta',
            ]),
            fn() => $this->controller->login()
        );

        $this->assertFalse($response['success']);

        $this->assertStringContainsString(
            'Credenciales inválidas',
            $response['error']
        );
    }

    public function test_register_funciona_correctamente(): void
    {
        $this->service
            ->expects($this->once())
            ->method('registrar')
            ->with([
                'nombre' => 'Test',
                'apellido' => 'User',
                'email' => 'test@test.com',
                'contrasena' => 'password123',
                'telefono' => '123456789',
                'domicilio' => 'Calle 123',
            ]);

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Test',
                'apellido' => 'User',
                'email' => 'test@test.com',
                'contrasena' => 'password123',
                'telefono' => '123456789',
                'domicilio' => 'Calle 123',
            ]),
            fn() => $this->controller->register()
        );

        $this->assertTrue($response['success']);

        $this->assertStringContainsString(
            'Usuario registrado',
            $response['message']
        );
    }

    public function test_refresh_funciona_correctamente(): void
    {
        $resultado = [
            'access_token' => 'nuevo-access-token',
            'refresh_token' => 'nuevo-refresh-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ];

        $this->service
            ->expects($this->once())
            ->method('refresh')
            ->with([
                'refresh_token' => 'token_viejo_enviado_por_cliente',
            ])
            ->willReturn($resultado);

        $response = $this->captureJsonWithBody(
            json_encode([
                'refresh_token' => 'token_viejo_enviado_por_cliente',
            ]),
            fn() => $this->controller->refresh()
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            $resultado,
            $response['data']
        );

        $this->assertStringContainsString(
            'Token refrescado correctamente',
            $response['message']
        );
    }

    public function test_refresh_devuelve_error_si_el_token_no_es_valido(): void
    {
        $this->service
            ->expects($this->once())
            ->method('refresh')
            ->with([
                'refresh_token' => 'token_invalido',
            ])
            ->willThrowException(
                new UnauthorizedException(
                    'Refresh token inválido'
                )
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'refresh_token' => 'token_invalido',
            ]),
            fn() => $this->controller->refresh()
        );

        $this->assertFalse($response['success']);

        $this->assertStringContainsString(
            'Refresh token inválido',
            $response['error']
        );
    }

    public function test_refresh_devuelve_error_de_validacion(): void
    {
        $this->service
            ->expects($this->once())
            ->method('refresh')
            ->with([])
            ->willThrowException(
                new ValidationException([
                    'refresh_token' => [
                        'El refresh token es requerido',
                    ],
                ])
            );

        $response = $this->captureJsonWithBody(
            json_encode([]),
            fn() => $this->controller->refresh()
        );

        $this->assertFalse($response['success']);

        $this->assertArrayHasKey(
            'validation_errors',
            $response
        );

        $this->assertSame(
            [
                'refresh_token' => [
                    'El refresh token es requerido',
                ],
            ],
            $response['validation_errors']
        );
    }

    public function test_logout_funciona_correctamente(): void
    {
        $this->service
            ->expects($this->once())
            ->method('logout')
            ->with([
                'refresh_token' => 'token_a_invalidar',
            ]);

        $response = $this->captureJsonWithBody(
            json_encode([
                'refresh_token' => 'token_a_invalidar',
            ]),
            fn() => $this->controller->logout()
        );

        $this->assertTrue($response['success']);

        $this->assertStringContainsString(
            'Sesión cerrada correctamente',
            $response['message']
        );
    }
}