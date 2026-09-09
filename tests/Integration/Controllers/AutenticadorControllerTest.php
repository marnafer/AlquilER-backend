<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\AutenticadorController;
use App\Services\AutenticadorService;

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
            'password' => 'password123'
        ]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('login')
            ->willReturn(['token' => 'jwt_token', 'user' => ['id' => 1]]);

        ob_start();
        $this->controller->login();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('"token"', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_login_missing_fields()
    {
        $input = json_encode(['email' => 'test@test.com']);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->login();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_can_register()
    {
        $input = json_encode([
            'nombre' => 'Test',
            'apellido' => 'User',
            'email' => 'test@test.com',
            'password' => 'password123',
            'telefono' => '123456789',
            'domicilio' => 'Calle 123'
        ]);
        file_put_contents('php://input', $input);

        $this->service
            ->expects($this->once())
            ->method('register')
            ->willReturn(['id' => 1]);

        ob_start();
        $this->controller->register();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
        $this->assertStringContainsString('201', $output);
    }

    /** @test */
    public function it_returns_bad_request_when_register_missing_fields()
    {
        $input = json_encode(['email' => 'test@test.com']);
        file_put_contents('php://input', $input);

        ob_start();
        $this->controller->register();
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('400', $output);
    }

    /** @test */
    public function it_can_logout()
    {
        $request = $this->createRequest(['usuario_id' => 1]);

        $this->service
            ->expects($this->once())
            ->method('logout')
            ->willReturn(true);

        ob_start();
        $this->controller->logout($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":true', $output);
    }

    /** @test */
    public function it_returns_unauthorized_when_logout_without_user()
    {
        $request = $this->createRequest([]);

        ob_start();
        $this->controller->logout($request);
        $output = ob_get_clean();

        $this->assertStringContainsString('"success":false', $output);
        $this->assertStringContainsString('401', $output);
    }
}