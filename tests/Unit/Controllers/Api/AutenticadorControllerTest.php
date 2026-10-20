<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\AutenticadorController;
use App\Services\AutenticadorService;
use Tests\TestCase;

final class AutenticadorControllerTest extends TestCase
{
    private AutenticadorService $service;
    private AutenticadorController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(AutenticadorService::class);
        $this->controller = new AutenticadorController($this->service);
    }

    public function test_login_devuelve_el_token(): void
    {
        $datos = ['email' => 'a@b.com', 'password' => 'secret'];
        $token = ['access_token' => 'abc', 'token_type' => 'Bearer'];

        $this->service
            ->expects($this->once())
            ->method('login')
            ->with($datos)
            ->willReturn($token);

        $response = $this->controller->login($this->makeJsonRequest($datos));

        $this->assertSame(200, $response->status());
        $this->assertSame($token, $response->payload()['data']);
    }

    public function test_login_no_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->once())
            ->method('login')
            ->with(['email' => 'x@y.z'])
            ->willReturn(['access_token' => 't']);

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

    public function test_refresh_devuelve_el_token_nuevo(): void
    {
        $datos = ['refresh_token' => 'viejo'];
        $nuevo = ['access_token' => 'nuevo'];

        $this->service
            ->expects($this->once())
            ->method('refresh')
            ->with($datos)
            ->willReturn($nuevo);

        $response = $this->controller->refresh($this->makeJsonRequest($datos));

        $this->assertSame(200, $response->status());
        $this->assertSame($nuevo, $response->payload()['data']);
        $this->assertSame(
            'Token refrescado correctamente',
            $response->payload()['message']
        );
    }

    public function test_logout_devuelve_200(): void
    {
        $datos = ['refresh_token' => 'abc'];

        $this->service
            ->expects($this->once())
            ->method('logout')
            ->with($datos);

        $response = $this->controller->logout($this->makeJsonRequest($datos));

        $this->assertSame(200, $response->status());
        $this->assertSame([], $response->payload()['data']);
        $this->assertSame(
            'Sesión cerrada correctamente',
            $response->payload()['message']
        );
    }
}
