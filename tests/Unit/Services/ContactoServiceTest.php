<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ValidationException;
use App\Services\ContactoService;
use App\Services\MailService;
use PHPUnit\Framework\TestCase;

final class ContactoServiceTest extends TestCase
{
    public function test_enviar_llama_al_mail_service_con_destino_y_reply_to(): void
    {
        $mailService = $this->createMock(MailService::class);

        $mailService->expects($this->once())
            ->method('enviar')
            ->with(
                'contacto@alquiler.com.ar',
                'Contacto - Consulta',
                $this->callback(
                    fn (string $html): bool =>
                        str_contains($html, 'María Gómez')
                        && str_contains($html, 'maria@test.com')
                ),
                $this->callback(
                    fn (string $texto): bool =>
                        str_contains($texto, 'Hola, quería consultar disponibilidad.')
                ),
                'maria@test.com'
            );

        $servicio = new ContactoService(
            $mailService,
            'contacto@alquiler.com.ar'
        );

        $servicio->enviar([
            'nombre' => 'María Gómez',
            'email' => 'maria@test.com',
            'asunto' => 'Consulta',
            'mensaje' => 'Hola, quería consultar disponibilidad.',
        ]);
    }

    public function test_enviar_lanza_error_si_el_destino_no_esta_configurado(): void
    {
        $mailService = $this->createMock(MailService::class);

        $mailService->expects($this->never())
            ->method('enviar');

        $servicio = new ContactoService($mailService, '  ');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El servicio de contacto no está configurado');

        $servicio->enviar([
            'nombre' => 'María Gómez',
            'email' => 'maria@test.com',
            'asunto' => 'Consulta',
            'mensaje' => 'Hola, quería consultar disponibilidad.',
        ]);
    }

    public function test_enviar_lanza_validacion_si_faltan_campos(): void
    {
        $mailService = $this->createMock(MailService::class);

        $mailService->expects($this->never())
            ->method('enviar');

        $servicio = new ContactoService(
            $mailService,
            'contacto@alquiler.com.ar'
        );

        $this->expectException(ValidationException::class);

        $servicio->enviar([
            'nombre' => '',
            'email' => 'no-valid@',
            'asunto' => 'x',
            'mensaje' => '',
        ]);
    }
}