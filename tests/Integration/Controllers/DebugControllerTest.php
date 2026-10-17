<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\DebugController;
use App\Debug\Debugger;
use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\TestCase;

/**
 * Cubre los endpoints de debug que NO llaman a exit().
 * DebugController::phpinfo() queda fuera a proposito: hace exit() y
 * abortaria el proceso de PHPUnit.
 */
class DebugControllerTest extends TestCase
{
    private DebugController $controller;
    private string $logFile;
    private ?string $logOriginal;

    protected function setUp(): void
    {
        parent::setUp();

        // El controlador usa Eloquent directamente, hay que apuntarlo
        // a la base real (igual que IntegridadBaseDeDatosTest).
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'database'  => 'sistema_alquiler_db_dev',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->controller = new DebugController();

        $this->logFile = Debugger::getLogFile();
        $this->logOriginal = is_file($this->logFile)
            ? (string) file_get_contents($this->logFile)
            : null;
    }

    protected function tearDown(): void
    {
        // Restaura el log de debug real para no alterarlo.
        @unlink($this->logFile);
        if ($this->logOriginal !== null) {
            file_put_contents($this->logFile, $this->logOriginal);
        }

        parent::tearDown();
    }

    private function ejecutar(callable $fn): array
    {
        $decoded = json_decode($this->captureResponse($fn), true);

        return is_array($decoded) ? $decoded : [];
    }

    public function test_stats_devuelve_el_resumen_de_entidades(): void
    {
        $response = $this->ejecutar(
            fn() => $this->controller->stats()
        );

        $this->assertTrue($response['success']);

        $data = $response['data'];

        $this->assertSame(14, $data['total_entidades']);
        $this->assertIsInt($data['total_registros']);
        $this->assertCount(14, $data['detalle_por_entidad']);
        $this->assertArrayHasKey(
            'categorias',
            $data['detalle_por_entidad']
        );
        $this->assertArrayHasKey(
            'propiedad_imagenes',
            $data['detalle_por_entidad']
        );
        $this->assertIsInt($data['total_logs']);
        $this->assertTrue($data['debug_enabled']);
    }

    public function test_testdb_confirma_la_conexion(): void
    {
        $response = $this->ejecutar(
            fn() => $this->controller->testDB()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Conexión a base de datos exitosa',
            $response['message']
        );
        $this->assertContains(
            $response['data']['test'],
            [
                'OK - Conexión funcionando',
                'Sin datos pero conexión ok',
            ]
        );
        $this->assertSame(
            'sistema_alquiler_db',
            $response['data']['database']
        );
        $this->assertSame('Eloquent ORM', $response['data']['driver']);
    }

    public function test_logs_sin_archivo_avisa_que_no_hay_logs(): void
    {
        @unlink($this->logFile);

        $response = $this->ejecutar(
            fn() => $this->controller->logs()
        );

        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']);
        $this->assertSame(
            'No hay archivo de logs',
            $response['message']
        );
    }

    public function test_logs_devuelve_las_entradas_en_orden_inverso(): void
    {
        file_put_contents(
            $this->logFile,
            '{"m":"primero"}' . PHP_EOL . '{"m":"segundo"}' . PHP_EOL
        );

        $response = $this->ejecutar(
            fn() => $this->controller->logs()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(2, $response['total']);
        $this->assertSame('segundo', $response['data'][0]['m']);
        $this->assertSame('primero', $response['data'][1]['m']);
    }

    public function test_logs_ignora_las_lineas_invalidas(): void
    {
        file_put_contents(
            $this->logFile,
            'no-es-json' . PHP_EOL . '{"m":"ok"}' . PHP_EOL
        );

        $response = $this->ejecutar(
            fn() => $this->controller->logs()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(1, $response['total']);
        $this->assertSame('ok', $response['data'][0]['m']);
    }

    public function test_clearlog_vacia_el_archivo_existente(): void
    {
        file_put_contents($this->logFile, 'contenido previo');

        $response = $this->ejecutar(
            fn() => $this->controller->clearLog()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Log limpiado exitosamente',
            $response['message']
        );
        $this->assertSame(
            '',
            (string) file_get_contents($this->logFile)
        );
    }

    public function test_clearlog_sin_archivo_no_falla(): void
    {
        @unlink($this->logFile);

        $response = $this->ejecutar(
            fn() => $this->controller->clearLog()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'No existe archivo de log para limpiar',
            $response['message']
        );
    }
}
