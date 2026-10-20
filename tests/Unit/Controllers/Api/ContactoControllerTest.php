<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\ContactoController;
use App\Services\ContactoService;
use Tests\TestCase;

final class ContactoControllerTest extends TestCase
{
    public function test_store_envia_el_mensaje_de_contacto(): void
    {
        $payload = [
            'nombre' => 'Ana Gomez',
            'email' => 'ana@example.com',
            'asunto' => 'Consulta',
            'mensaje' => 'Hola, quisiera mas informacion.',
        ];

        $service = $this->createMock(ContactoService::class);
        $service
            ->expects($this->once())
            ->method('enviar')
            ->with($payload);

        $controller = new ContactoController($service);

        $response = $controller->store($this->makeJsonRequest($payload));

        $this->assertSame(201, $response->status());
        $this->assertSame([], $response->payload()['data']);
        $this->assertSame(
            'Mensaje enviado correctamente',
            $response->payload()['message']
        );
    }

    public function test_store_no_requiere_autenticacion(): void
    {
        $service = $this->createMock(ContactoService::class);
        $service
            ->expects($this->once())
            ->method('enviar')
            ->with(['nombre' => 'x']);

        $controller = new ContactoController($service);

        $response = $controller->store($this->makeJsonRequest(['nombre' => 'x']));

        $this->assertSame(201, $response->status());
    }
}
