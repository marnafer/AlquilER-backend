<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Services\ContactoService;
use App\Services\EnviadorCorreoInterface;
use PHPUnit\Framework\TestCase;

final class ContactoServiceCoverageTest extends TestCase
{
    public function test_enviar_lanza_excepcion_si_el_correo_no_se_envia(): void
    {
        $mailService = $this->createMock(EnviadorCorreoInterface::class);
        $mailService
            ->expects($this->once())
            ->method('enviar')
            ->willReturn(false);

        $service = new ContactoService($mailService, 'destino@example.com');

        $this->expectException(BadRequestException::class);

        $service->enviar([
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
            'asunto' => 'Consulta',
            'mensaje' => 'Hola mundo, este es un mensaje de prueba',
        ]);
    }
}