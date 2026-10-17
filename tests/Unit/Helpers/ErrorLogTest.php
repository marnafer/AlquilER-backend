<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ErrorLog;
use RuntimeException;
use Tests\TestCase;

class ErrorLogTest extends TestCase
{
    private const BYTES_MAXIMOS = 5 * 1024 * 1024;

    private string $ruta;
    private string $rutaRotada;
    private string $errorLogPrevio;
    private string $errorLogTemporal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ruta = ErrorLog::ruta();
        $this->rutaRotada = $this->ruta . '.1';

        // Desvia error_log() a un archivo temporal para no ensuciar
        // la salida de PHPUnit ni el log real del servidor.
        $this->errorLogPrevio = (string) ini_get('error_log');
        $this->errorLogTemporal =
            sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'alquiler-errorlog-' . uniqid() . '.log';
        ini_set('error_log', $this->errorLogTemporal);
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->errorLogPrevio);
        @unlink($this->errorLogTemporal);

        parent::tearDown();
    }

    public function test_ruta_apunta_al_log_de_errores(): void
    {
        $this->assertStringEndsWith(
            'storage' . DIRECTORY_SEPARATOR . 'errores.log',
            ErrorLog::ruta()
        );
    }

    public function test_registrar_agrega_el_detalle_de_la_excepcion(): void
    {
        $this->conRespaldoDeLogs(function (): void {
            ErrorLog::registrar(
                new RuntimeException('marca-unica-12345')
            );

            $this->assertFileExists($this->ruta);

            $contenido = (string) file_get_contents($this->ruta);

            $this->assertStringContainsString(
                'RuntimeException',
                $contenido
            );
            $this->assertStringContainsString(
                'marca-unica-12345',
                $contenido
            );
        });
    }

    public function test_registrar_rota_el_log_cuando_supera_el_maximo(): void
    {
        $this->conRespaldoDeLogs(function (): void {
            file_put_contents(
                $this->ruta,
                str_repeat('x', self::BYTES_MAXIMOS + 10)
            );
            clearstatcache(true, $this->ruta);

            ErrorLog::registrar(
                new RuntimeException('despues-de-rotar')
            );

            $this->assertFileExists($this->rutaRotada);
            $this->assertStringContainsString(
                'despues-de-rotar',
                (string) file_get_contents($this->ruta)
            );
            $this->assertStringStartsWith(
                'x',
                (string) file_get_contents($this->rutaRotada)
            );
        });
    }

    /**
     * Respalda el log (y su version rotada) y los restaura al terminar,
     * para no alterar el archivo real del backend.
     */
    private function conRespaldoDeLogs(callable $fn): void
    {
        $original = is_file($this->ruta)
            ? (string) file_get_contents($this->ruta)
            : null;
        $originalRotado = is_file($this->rutaRotada)
            ? (string) file_get_contents($this->rutaRotada)
            : null;

        try {
            $fn();
        } finally {
            @unlink($this->ruta);
            @unlink($this->rutaRotada);

            if ($original !== null) {
                file_put_contents($this->ruta, $original);
            }
            if ($originalRotado !== null) {
                file_put_contents($this->rutaRotada, $originalRotado);
            }
        }
    }
}
