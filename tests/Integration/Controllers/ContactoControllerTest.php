<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\ContactoController;
use App\Services\ContactoService;
use Tests\TestCase;

final class ContactoControllerTest extends TestCase
{
    private ContactoService $service;
    private ContactoController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(ContactoService::class);

        $this->controller = new ContactoController($this->service);
    }

    public function test_store_envia_el_mensaje_de_contacto(): void
    {
        $payload = [
            'nombre' => 'Ana Gomez',
            'email' => 'ana@example.com',
            'asunto' => 'Consulta',
            'mensaje' => 'Hola, quisiera mas informacion.',
        ];

        $this->service
            ->expects($this->once())
            ->method('enviar')
            ->with($payload);

        $response = $this->captureJsonWithBody(
            json_encode($payload),
            fn() => $this->controller->store()
        );

        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']);
        $this->assertSame(
            'Mensaje enviado correctamente',
            $response['message']
        );
        $this->assertSame(201, http_response_code());
    }
}
