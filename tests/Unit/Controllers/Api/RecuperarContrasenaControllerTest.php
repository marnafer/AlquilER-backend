<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

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
        $this->controller = new RecuperarContrasenaController($this->service);
    }

    public function test_solicitar_devuelve_200_con_mensaje_generico(): void
    {
        $datos = ['email' => 'ana@example.com'];

        $this->service
            ->expects($this->once())
            ->method('solicitar')
            ->with($datos);

        $response = $this->controller->solicitar(
            $this->makeJsonRequest($datos)
        );

        $this->assertSame(200, $response->status());
        $this->assertSame([], $response->payload()['data']);
        $this->assertSame(
            'Si el correo existe, recibirás un enlace para restablecer tu contraseña',
            $response->payload()['message']
        );
    }

    public function test_solicitar_no_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->once())
            ->method('solicitar')
            ->with(['email' => 'x@y.z']);

        $response = $this->controller->solicitar(
            $this->makeJsonRequest(['email' => 'x@y.z'])
        );

        $this->assertSame(200, $response->status());
    }

    public function test_restablecer_devuelve_200(): void
    {
        $datos = ['token' => 'abc', 'password' => 'nueva'];

        $this->service
            ->expects($this->once())
            ->method('restablecer')
            ->with($datos);

        $response = $this->controller->restablecer(
            $this->makeJsonRequest($datos)
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Contraseña restablecida correctamente',
            $response->payload()['message']
        );
    }

    public function test_confirmar_cambio_devuelve_200(): void
    {
        $datos = ['email' => 'ana@example.com', 'token' => 'abc'];

        $this->service
            ->expects($this->once())
            ->method('confirmarCambio')
            ->with('ana@example.com', 'abc');

        $response = $this->controller->confirmarCambio(
            $this->makeJsonRequest($datos)
        );

        $this->assertSame(200, $response->status());
    }
}
