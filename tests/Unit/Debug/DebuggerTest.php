<?php

declare(strict_types=1);

namespace Tests\Unit\Debug;

use App\Debug\Debugger;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class DebuggerTest extends TestCase
{
    private ReflectionProperty $logFileProp;

    private ReflectionProperty $enabledProp;

    private string $archivo;

    private mixed $logFileOriginal;

    private mixed $enabledOriginal;

    private int $errorReportingOriginal;

    private string|false $displayErrorsOriginal;

    private string|false $displayStartupErrorsOriginal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logFileProp = new ReflectionProperty(Debugger::class, 'logFile');
        $this->enabledProp = new ReflectionProperty(Debugger::class, 'enabled');

        $this->logFileOriginal = $this->logFileProp->getValue();
        $this->enabledOriginal = $this->enabledProp->getValue();

        $this->archivo = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'debugger_test_'
            . bin2hex(random_bytes(4))
            . '.log';

        $this->logFileProp->setValue(null, $this->archivo);

        Debugger::setEnabled(true);

        $this->errorReportingOriginal = error_reporting();
        $this->displayErrorsOriginal = ini_get('display_errors');
        $this->displayStartupErrorsOriginal = ini_get('display_startup_errors');
    }

    protected function tearDown(): void
    {
        if (is_file($this->archivo)) {
            @unlink($this->archivo);
        }

        $this->logFileProp->setValue(null, $this->logFileOriginal);
        $this->enabledProp->setValue(null, $this->enabledOriginal);

        error_reporting($this->errorReportingOriginal);

        if ($this->displayErrorsOriginal !== false) {
            ini_set('display_errors', (string) $this->displayErrorsOriginal);
        }
        if ($this->displayStartupErrorsOriginal !== false) {
            ini_set('display_startup_errors', (string) $this->displayStartupErrorsOriginal);
        }

        parent::tearDown();
    }

    private function lineasDelLog(): array
    {
        if (!is_file($this->archivo)) {
            return [];
        }

        $contenido = trim((string) file_get_contents($this->archivo));

        if ($contenido === '') {
            return [];
        }

        return array_map(
            static fn (string $linea): mixed => json_decode($linea, true),
            explode(PHP_EOL, $contenido)
        );
    }

    public function test_get_log_file_por_defecto_apunta_al_debug_log_de_la_raiz(): void
    {
        $this->logFileProp->setValue(null, null);

        $ruta = Debugger::getLogFile();

        $this->assertStringEndsWith('debug.log', $ruta);
        $this->assertSame(
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'debug.log',
            $ruta
        );
    }

    public function test_log_escribe_una_linea_json_con_los_datos(): void
    {
        Debugger::log('mensaje de prueba', ['clave' => 'valor'], 'DEBUG');

        $lineas = $this->lineasDelLog();

        $this->assertCount(1, $lineas);
        $this->assertSame('DEBUG', $lineas[0]['type']);
        $this->assertSame('mensaje de prueba', $lineas[0]['message']);
        $this->assertSame(['clave' => 'valor'], $lineas[0]['data']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $lineas[0]['timestamp']
        );
    }

    public function test_log_usa_tipo_info_y_data_null_por_defecto(): void
    {
        Debugger::log('solo mensaje');

        $lineas = $this->lineasDelLog();

        $this->assertCount(1, $lineas);
        $this->assertSame('INFO', $lineas[0]['type']);
        $this->assertNull($lineas[0]['data']);
    }

    public function test_log_no_escapa_caracteres_unicode(): void
    {
        Debugger::log('información de sesión');

        $contenido = (string) file_get_contents($this->archivo);

        $this->assertStringContainsString('información de sesión', $contenido);
        $this->assertStringNotContainsString('informaci\u00f3n', $contenido);
    }

    public function test_log_acumula_varias_lineas(): void
    {
        Debugger::log('primero');
        Debugger::log('segundo');

        $lineas = $this->lineasDelLog();

        $this->assertCount(2, $lineas);
        $this->assertSame('primero', $lineas[0]['message']);
        $this->assertSame('segundo', $lineas[1]['message']);
    }

    public function test_log_no_escribe_si_esta_deshabilitado(): void
    {
        Debugger::setEnabled(false);

        Debugger::log('no deberia registrarse');

        $this->assertFileDoesNotExist($this->archivo);
    }

    public function test_dump_imprime_etiqueta_y_valor_cuando_esta_habilitado(): void
    {
        ob_start();
        Debugger::dump(['a' => 1], 'Etiqueta');
        $salida = (string) ob_get_clean();

        $this->assertStringContainsString('<div', $salida);
        $this->assertStringContainsString('<strong>Etiqueta:</strong>', $salida);
        $this->assertStringContainsString('<pre>', $salida);
        $this->assertStringContainsString('Array', $salida);
    }

    public function test_dump_sin_etiqueta_no_imprime_label(): void
    {
        ob_start();
        Debugger::dump('un valor');
        $salida = (string) ob_get_clean();

        $this->assertStringNotContainsString('<strong>', $salida);
        $this->assertStringContainsString('un valor', $salida);
    }

    public function test_dump_no_imprime_si_esta_deshabilitado(): void
    {
        Debugger::setEnabled(false);

        ob_start();
        Debugger::dump('oculto', 'Label');
        $salida = (string) ob_get_clean();

        $this->assertSame('', $salida);
    }

    public function test_time_devuelve_resultado_tiempo_y_registra(): void
    {
        $resultado = Debugger::time(static fn (): int => 6 * 7, 'calculo');

        $this->assertSame(42, $resultado['result']);
        $this->assertIsFloat($resultado['time_ms']);
        $this->assertGreaterThanOrEqual(0.0, $resultado['time_ms']);

        $lineas = $this->lineasDelLog();

        $this->assertCount(1, $lineas);
        $this->assertSame('calculo', $lineas[0]['message']);
        $this->assertSame(42, $lineas[0]['data']['result']);
        $this->assertArrayHasKey('time_ms', $lineas[0]['data']);
    }

    public function test_time_sin_etiqueta_usa_mensaje_por_defecto(): void
    {
        Debugger::time(static fn () => null);

        $lineas = $this->lineasDelLog();

        $this->assertCount(1, $lineas);
        $this->assertSame('Time measurement', $lineas[0]['message']);
    }

    public function test_response_registra_el_codigo_y_los_datos(): void
    {
        Debugger::response(['ok' => true], 201);

        $lineas = $this->lineasDelLog();

        $this->assertCount(1, $lineas);
        $this->assertSame('API Response', $lineas[0]['message']);
        $this->assertSame(201, $lineas[0]['data']['status_code']);
        $this->assertSame(['ok' => true], $lineas[0]['data']['data']);
    }

    public function test_response_usa_200_por_defecto(): void
    {
        Debugger::response(['x' => 1]);

        $lineas = $this->lineasDelLog();

        $this->assertSame(200, $lineas[0]['data']['status_code']);
    }

    public function test_response_no_escribe_si_esta_deshabilitado(): void
    {
        Debugger::setEnabled(false);

        Debugger::response(['x' => 1], 500);

        $this->assertFileDoesNotExist($this->archivo);
    }

    public function test_enable_error_reporting_activa_todo(): void
    {
        error_reporting(0);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');

        Debugger::enableErrorReporting();

        $this->assertSame(E_ALL, error_reporting());
        $this->assertSame('1', ini_get('display_errors'));
        $this->assertSame('1', ini_get('display_startup_errors'));
    }

    public function test_get_stats_sin_archivo_devuelve_cero_logs(): void
    {
        $stats = Debugger::getStats();

        $this->assertSame(0, $stats['total_logs']);
        $this->assertSame($this->archivo, $stats['log_file']);
        $this->assertTrue($stats['enabled']);
    }

    public function test_get_stats_cuenta_las_lineas(): void
    {
        Debugger::log('uno');
        Debugger::log('dos');
        Debugger::log('tres');

        $stats = Debugger::getStats();

        $this->assertSame(3, $stats['total_logs']);
    }

    public function test_get_stats_refleja_que_esta_deshabilitado(): void
    {
        Debugger::setEnabled(false);

        $this->assertFalse(Debugger::getStats()['enabled']);
    }

    public function test_clear_log_borra_el_archivo(): void
    {
        Debugger::log('algo');

        $this->assertFileExists($this->archivo);

        Debugger::clearLog();

        $this->assertFileDoesNotExist($this->archivo);
    }

    public function test_clear_log_sin_archivo_no_falla(): void
    {
        $this->assertFileDoesNotExist($this->archivo);

        Debugger::clearLog();

        $this->assertFileDoesNotExist($this->archivo);
    }
}
