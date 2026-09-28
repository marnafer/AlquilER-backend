<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\MailService;
use PHPUnit\Framework\TestCase;

final class MailServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_ENV['MAIL_HOST']);
        unset($_ENV['MAIL_PORT']);
        unset($_ENV['MAIL_FROM']);
        unset($_ENV['MAIL_FROM_NAME']);
        unset($_ENV['MAIL_ENCRYPTION']);
    }

    public function test_enviar_sin_host_guarda_en_log_y_devuelve_true(): void
    {
        $mail = new MailService();

        $resultado = $mail->enviar(
            'destino@test.com',
            'Asunto de prueba',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertTrue($resultado);
    }

    public function test_enviar_sin_destinatario_devuelve_false(): void
    {
        $mail = new MailService(
            null,
            null,
            null,
            null,
            'emisor@test.com'
        );

        $resultado = $mail->enviar(
            '   ',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertFalse($resultado);
    }

    public function test_enviar_sin_asunto_devuelve_false(): void
    {
        $mail = new MailService();

        $resultado = $mail->enviar(
            'destino@test.com',
            ' ',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertFalse($resultado);
    }

    public function test_desde_entorno_toma_los_valores_del_entorno(): void
    {
        $_ENV['MAIL_HOST'] = 'smtp.test.com';
        $_ENV['MAIL_PORT'] = '2525';
        $_ENV['MAIL_FROM'] = 'origen@test.com';
        $_ENV['MAIL_FROM_NAME'] = 'Origen Test';
        $_ENV['MAIL_ENCRYPTION'] = 'ssl';

        $mail = MailService::desdeEntorno();

        $reflexion = new \ReflectionClass($mail);

        $this->assertSame('smtp.test.com', $reflexion->getProperty('host')->getValue($mail));
        $this->assertSame(2525, $reflexion->getProperty('port')->getValue($mail));
        $this->assertSame('origen@test.com', $reflexion->getProperty('from')->getValue($mail));
        $this->assertSame('Origen Test', $reflexion->getProperty('fromName')->getValue($mail));
        $this->assertSame('ssl', $reflexion->getProperty('encryption')->getValue($mail));
    }

    public function test_desde_entorno_usa_valores_por_defecto_cuando_faltan(): void
    {
        $mail = MailService::desdeEntorno();

        $reflexion = new \ReflectionClass($mail);

        $this->assertNull($reflexion->getProperty('host')->getValue($mail));
        $this->assertNull($reflexion->getProperty('port')->getValue($mail));
        $this->assertSame('no-responder@alquiler.local', $reflexion->getProperty('from')->getValue($mail));
        $this->assertSame('AlquilER', $reflexion->getProperty('fromName')->getValue($mail));
        $this->assertSame('tls', $reflexion->getProperty('encryption')->getValue($mail));
    }
}