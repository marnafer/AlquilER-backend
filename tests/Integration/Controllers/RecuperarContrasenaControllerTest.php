<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\RecuperarContrasenaController;
use App\Services\RecuperarContrasenaService;
use Tests\TestCase;

final class RecuperarContrasenaControllerTest extends TestCase
{
    private RecuperarContrasenaService $service;
    private RecuperarContrasenaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(
            RecuperarContrasenaService::class
        );

        $this->controller = new RecuperarContrasenaController(
            $this->service
        );
    }

    public function test_solicitar_delega_en_el_servicio(): void
    {
        $payload = ['email' => 'ana@example.com'];

        $this->service
            ->expects($this->once())
            ->method('solicitar')
            ->with($payload);

        $response = $this->captureJsonWithBody(
            json_encode($payload),
            fn() => $this->controller->solicitar()
        );

        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']);
        $this->assertSame(
            'Si el correo existe, recibirás un enlace para restablecer tu contraseña',
            $response['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_restablecer_delega_en_el_servicio(): void
    {
        $payload = [
            'token' => 'token-de-prueba',
            'password' => 'NuevaContrasena123',
            'password_confirmation' => 'NuevaContrasena123',
        ];

        $this->service
            ->expects($this->once())
            ->method('restablecer')
            ->with($payload);

        $response = $this->captureJsonWithBody(
            json_encode($payload),
            fn() => $this->controller->restablecer()
        );

        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']);
        $this->assertSame(
            'Contraseña restablecida correctamente',
            $response['message']
        );
        $this->assertSame(200, http_response_code());
    }
}
