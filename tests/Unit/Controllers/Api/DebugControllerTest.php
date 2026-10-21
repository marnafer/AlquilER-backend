<?php
declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\DebugController;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * DebugController usa Eloquent de fondo, asï¿½ï¿½ que solo se prueban las rutas
 * que no tocan la base de datos ni los modelos, mï¿½s stats y testDB cuando
 * la base de datos estï¿½ disponible.
 */
final class DebugControllerTest extends TestCase
{
    private DebugController $controller;

    private static bool $baseDeDatosLista = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$baseDeDatosLista) {
            try {
                $raiz = dirname(__DIR__, 4);

                if (file_exists($raiz . '/.env')) {
                    $dotenv = \Dotenv\Dotenv::createImmutable($raiz);
                    $dotenv->safeLoad();
                }

                if (!file_exists($raiz . '/src/database.php')) {
                    throw new \RuntimeException('No existe database.php');
                }

                require $raiz . '/src/database.php';

                Capsule::connection()->getPdo();

                self::$baseDeDatosLista = true;
            } catch (\Throwable $e) {
                self::$baseDeDatosLista = false;
            }
        }

        $this->controller = new DebugController();
    }

    private function omitirSiSinBaseDeDatos(): void
    {
        if (!self::$baseDeDatosLista) {
            $this->markTestSkipped(
                'La base de datos no estï¿½ disponible'
            );
        }
    }

    public function test_logs_devuelve_un_arreglo(): void
    {
        $response = $this->controller->logs($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertIsArray($response->payload()['data']);
    }

    public function test_logs_no_requiere_autenticacion(): void
    {
        $response = $this->controller->logs($this->makeRequest());

        $this->assertSame(200, $response->status());
    }

    public function test_clear_log_devuelve_200(): void
    {
        $response = $this->controller->clearLog($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertContains(
            $response->payload()['message'],
            ['Log limpiado exitosamente', 'No existe archivo de log para limpiar']
        );
    }

    public function test_clear_log_no_requiere_autenticacion(): void
    {
        $response = $this->controller->clearLog($this->makeRequest());

        $this->assertSame(200, $response->status());
    }

    public function test_phpinfo_devuelve_el_html(): void
    {
        $response = $this->controller->phpinfo($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertArrayHasKey('phpinfo', $response->payload());
        $this->assertNotEmpty($response->payload()['phpinfo']);
    }

    public function test_stats_devuelve_un_arreglo(): void
    {
        $this->omitirSiSinBaseDeDatos();

        $response = $this->controller->stats($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertArrayHasKey('total_entidades', $response->payload()['data']);
        $this->assertArrayHasKey('total_registros', $response->payload()['data']);
    }

    public function test_test_db_devuelve_ok(): void
    {
        $this->omitirSiSinBaseDeDatos();

        $response = $this->controller->testDB($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertArrayHasKey('test', $response->payload()['data']);
    }

    public function test_logs_sin_archivo_devuelve_mensaje(): void
    {
        $archivo = \App\Debug\Debugger::getLogFile();
        $existia = file_exists($archivo);
        $respaldo = $existia ? file_get_contents($archivo) : null;

        if ($existia) {
            unlink($archivo);
        }

        try {
            $response = $this->controller->logs($this->makeRequest());

            $this->assertSame(200, $response->status());
            $this->assertSame([], $response->payload()['data']);
            $this->assertSame(
                'No hay archivo de logs',
                $response->payload()['message']
            );
        } finally {
            if ($existia) {
                file_put_contents($archivo, $respaldo);
            } elseif (file_exists($archivo)) {
                unlink($archivo);
            }
        }
    }

    public function test_logs_ignora_lineas_json_invalidas(): void
    {
        $archivo = \App\Debug\Debugger::getLogFile();
        $existia = file_exists($archivo);
        $respaldo = $existia ? file_get_contents($archivo) : null;

        file_put_contents(
            $archivo,
            "linea invalida" . PHP_EOL . json_encode(['type' => 'INFO']) . PHP_EOL
        );

        try {
            $response = $this->controller->logs($this->makeRequest());

            $this->assertSame(200, $response->status());
            $this->assertCount(1, $response->payload()['data']);
        } finally {
            if ($existia) {
                file_put_contents($archivo, $respaldo);
            } else {
                unlink($archivo);
            }
        }
    }

    public function test_clear_log_sin_archivo_devuelve_mensaje(): void
    {
        $archivo = \App\Debug\Debugger::getLogFile();
        $existia = file_exists($archivo);
        $respaldo = $existia ? file_get_contents($archivo) : null;

        if ($existia) {
            unlink($archivo);
        }

        try {
            $response = $this->controller->clearLog($this->makeRequest());

            $this->assertSame(200, $response->status());
            $this->assertSame(
                'No existe archivo de log para limpiar',
                $response->payload()['message']
            );
        } finally {
            if ($existia) {
                file_put_contents($archivo, $respaldo);
            } elseif (file_exists($archivo)) {
                unlink($archivo);
            }
        }
    }
}
