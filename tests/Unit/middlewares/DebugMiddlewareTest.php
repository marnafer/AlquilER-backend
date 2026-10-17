<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use App\Debug\Debugger;
use App\Middlewares\DebugMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * DebugMiddleware::handle() llama a Debugger::request(), que usa
 * getallheaders(): esa funcion no existe en la SAPI CLI de PHPUnit.
 * Por eso se deshabilita el Debugger mientras corre handle() y se
 * reactiva solo para el callback, que registra via Debugger::log().
 */
final class DebugMiddlewareTest extends TestCase
{
    private string $logFile;
    private ?string $respaldo = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logFile = Debugger::getLogFile();

        $this->respaldo = is_file($this->logFile)
            ? (string) file_get_contents($this->logFile)
            : null;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/test';

        Debugger::setEnabled(true);
    }

    protected function tearDown(): void
    {
        Debugger::setEnabled(true);

        @unlink($this->logFile);
        if ($this->respaldo !== null) {
            file_put_contents($this->logFile, $this->respaldo);
        }

        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

        parent::tearDown();
    }

    public function test_handle_devuelve_un_callable(): void
    {
        Debugger::setEnabled(false);

        $this->assertIsCallable(DebugMiddleware::handle());
    }

    public function test_el_callback_devuelve_la_misma_respuesta(): void
    {
        Debugger::setEnabled(false);
        $callback = DebugMiddleware::handle();

        $respuesta = (object) ['status' => 200];

        Debugger::setEnabled(true);

        $this->assertSame($respuesta, $callback($respuesta));
    }

    public function test_el_callback_registra_la_peticion_completada(): void
    {
        Debugger::setEnabled(false);
        $callback = DebugMiddleware::handle();

        Debugger::setEnabled(true);
        $callback((object) ['ok' => true]);

        $contenido = is_file($this->logFile)
            ? (string) file_get_contents($this->logFile)
            : '';

        $this->assertStringContainsString(
            'Request completed',
            $contenido
        );
        $this->assertStringContainsString(
            'execution_time_ms',
            $contenido
        );
    }

    public function test_el_callback_no_registra_si_el_debugger_esta_deshabilitado(): void
    {
        Debugger::setEnabled(false);
        $callback = DebugMiddleware::handle();

        $respuesta = (object) ['x' => 1];

        $this->assertSame($respuesta, $callback($respuesta));
    }
}
