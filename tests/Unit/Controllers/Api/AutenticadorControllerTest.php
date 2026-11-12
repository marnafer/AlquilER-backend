<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\AutenticadorController;
use App\Services\AutenticadorService;
use Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class AutenticadorControllerTest extends TestCase
{
    /** @var AutenticadorService&MockObject */
    private AutenticadorService $service;
    private AutenticadorController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(AutenticadorService::class);
        $this->controller = new AutenticadorController($this->service);
    }

    public function test_login_establece_cookies_y_no_devuelve_los_tokens(): void
    {
        $datos = ['email' => 'a@b.com', 'password' => 'secret'];
        $tokens = [
            'access_token' => 'abc',
            'refresh_token' => 'refresh',
            'rol_id' => 2,
        ];

        $this->service
            ->expects($this->once())
            ->method('login')
            ->with($datos)
            ->willReturn($tokens);

        $response = $this->controller->login($this->makeJsonRequest($datos));

        $this->assertSame(200, $response->status());
        $this->assertSame(['rol_id' => 2], $response->payload()['data']);
        $this->assertCount(2, $response->cookies());
        $this->assertSame('access_token', $response->cookies()[0]['name']);
        $this->assertSame('abc', $response->cookies()[0]['value']);
        $this->assertSame('/', $response->cookies()[0]['options']['path']);
        $this->assertSame(
            '/api/autenticador',
            $response->cookies()[1]['options']['path']
        );
        $this->assertTrue($response->cookies()[0]['options']['httponly']);
        $this->assertTrue($response->cookies()[0]['options']['secure']);
        $this->assertSame('None', $response->cookies()[0]['options']['samesite']);
    }

    public function test_login_no_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->once())
            ->method('login')
            ->with(['email' => 'x@y.z'])
            ->willReturn([
                'access_token' => 't',
                'refresh_token' => 'r',
                'rol_id' => 1,
            ]);

        $response = $this->controller->login(
            $this->makeJsonRequest(['email' => 'x@y.z'])
        );

        $this->assertSame(200, $response->status());
    }

    public function test_register_devuelve_201(): void
    {
        $datos = ['email' => 'nuevo@b.com', 'password' => 'secret'];

        $this->service
            ->expects($this->once())
            ->method('registrar')
            ->with($datos);

        $response = $this->controller->register($this->makeJsonRequest($datos));

        $this->assertSame(201, $response->status());
        $this->assertSame([], $response->payload()['data']);
        $this->assertSame('Usuario registrado', $response->payload()['message']);
    }

    public function test_refresh_rota_cookies_sin_devolver_los_tokens(): void
    {
        $nuevo = [
            'access_token' => 'nuevo',
            'refresh_token' => 'refresh-nuevo',
            'rol_id' => 3,
        ];

        $this->service
            ->expects($this->once())
            ->method('refresh')
            ->with(['refresh_token' => 'viejo'])
            ->willReturn($nuevo);

        $response = $this->controller->refresh($this->makeRequest([
            'method' => 'POST',
            'cookies' => ['refresh_token' => 'viejo'],
        ]));

        $this->assertSame(200, $response->status());
        $this->assertSame(['rol_id' => 3], $response->payload()['data']);
        $this->assertCount(2, $response->cookies());
        $this->assertSame(
            'Token refrescado correctamente',
            $response->payload()['message']
        );
    }

    public function test_logout_devuelve_200(): void
    {
        $this->service
            ->expects($this->once())
            ->method('logout')
            ->with(['refresh_token' => 'abc']);

        $response = $this->controller->logout($this->makeRequest([
            'method' => 'POST',
            'cookies' => ['refresh_token' => 'abc'],
        ]));

        $this->assertSame(200, $response->status());
        $this->assertSame([], $response->payload()['data']);
        $this->assertCount(2, $response->cookies());
        $this->assertSame('', $response->cookies()[0]['value']);
        $this->assertSame(1, $response->cookies()[0]['options']['expires']);
        $this->assertSame(
            'Sesión cerrada correctamente',
            $response->payload()['message']
        );
    }

    public function test_logout_usa_refresh_token_del_body_si_no_hay_cookie(): void
    {
        $this->service
            ->expects($this->once())
            ->method('logout')
            ->with(['refresh_token' => 'del-body']);

        $response = $this->controller->logout($this->makeRequest([
            'method' => 'POST',
            'rawBody' => json_encode(['refresh_token' => 'del-body']),
        ]));

        $this->assertSame(200, $response->status());
    }

    public function test_refresh_usa_body_si_no_hay_cookie(): void
    {
        $this->service
            ->expects($this->once())
            ->method('refresh')
            ->with(['refresh_token' => 'del-body'])
            ->willReturn([
                'access_token' => 'nuevo',
                'refresh_token' => 'refresh-nuevo',
                'rol_id' => 3,
            ]);

        $response = $this->controller->refresh($this->makeRequest([
            'method' => 'POST',
            'rawBody' => json_encode(['refresh_token' => 'del-body']),
        ]));

        $this->assertSame(200, $response->status());
    }

    public function test_refresh_devuelve_null_si_body_vacio(): void
    {
        $this->service
            ->expects($this->once())
            ->method('refresh')
            ->with(['refresh_token' => null])
            ->willReturn([
                'access_token' => 'nuevo',
                'refresh_token' => 'refresh-nuevo',
                'rol_id' => 3,
            ]);

        $response = $this->controller->refresh($this->makeRequest([
            'method' => 'POST',
            'rawBody' => '',
        ]));

        $this->assertSame(200, $response->status());
    }

    public function test_verificar_devuelve_200(): void
    {
        $this->service
            ->expects($this->once())
            ->method('verificarEmail')
            ->with(['token' => 'abc']);

        $response = $this->controller->verificar($this->makeRequest([
            'method' => 'GET',
            'query' => ['token' => 'abc'],
        ]));

        $this->assertSame(200, $response->status());
        $this->assertSame([], $response->payload()['data']);
        $this->assertSame(
            'Correo verificado correctamente',
            $response->payload()['message']
        );
    }
}
