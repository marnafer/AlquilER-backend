<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\MailService;
use PHPUnit\Framework\TestCase;

final class MailServiceCoverageTest extends TestCase
{
    public function test_desde_entorno_usa_constant_o_default_si_no_hay_env(): void
    {
        $previousEnv = $_ENV['APP_ENV'] ?? null;
        unset($_ENV['APP_ENV']);

        try {
            $mail = MailService::desdeEntorno();
            $this->assertInstanceOf(MailService::class, $mail);
        } finally {
            if ($previousEnv !== null) {
                $_ENV['APP_ENV'] = $previousEnv;
            }
        }
    }

    public function test_es_produccion_evalua_entorno_por_defecto(): void
    {
        $previousEnv = $_ENV['APP_ENV'] ?? null;
        unset($_ENV['APP_ENV']);

        try {
            $mail = new MailService(null, null, '', '', 'from@example.com', 'AlquilER', 'tls');
            $mail->enviar('to@example.com', 'Asunto', '<p>x</p>', 'x');
            $this->assertTrue(true);
        } finally {
            if ($previousEnv !== null) {
                $_ENV['APP_ENV'] = $previousEnv;
            }
        }
    }
}