<?php

declare(strict_types=1);

namespace Tests\Unit\Performance;

use App\Performance\PerformanceMetricsBootstrap;
use App\Performance\PerformanceMetricsContext;
use App\Performance\PerformanceMetricsLogger;
use App\Performance\PerformanceMetricsSession;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class PerformanceMetricsBootstrapTest extends TestCase
{
    private string $directory;

    /** @var array<string, mixed> */
    private array $previousEnvironment;

    private string $previousErrorLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousEnvironment = $_ENV;
        $previousErrorLog = ini_get('error_log');
        $this->previousErrorLog = $previousErrorLog === false
            ? ''
            : $previousErrorLog;

        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'alquiler-performance-bootstrap-'
            . bin2hex(random_bytes(6));

        $this->assertTrue(mkdir($this->directory, 0700, true));

        ini_set(
            'error_log',
            $this->directory . DIRECTORY_SEPARATOR . 'php-error.log'
        );

        PerformanceMetricsContext::clear();
        unset(
            $_ENV['APP_ENV'],
            $_ENV['PERFORMANCE_METRICS_ENABLED'],
            $_ENV['PERFORMANCE_METRICS_SLOW_SQL_MS']
        );
    }

    protected function tearDown(): void
    {
        PerformanceMetricsContext::clear();
        $_ENV = $this->previousEnvironment;
        ini_set('error_log', $this->previousErrorLog);

        foreach ([
            'performance.jsonl',
            'performance.jsonl.lock',
            'php-error.log',
        ] as $filename) {
            $path = $this->directory
                . DIRECTORY_SEPARATOR
                . $filename;

            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach ([
            'sessions.json',
            'sessions.json.lock',
        ] as $filename) {
            $path = $this->directory
                . DIRECTORY_SEPARATOR
                . 'sessions'
                . DIRECTORY_SEPARATOR
                . $filename;

            if (is_file($path)) {
                @unlink($path);
            }
        }

        $sessionDirectory = $this->directory
            . DIRECTORY_SEPARATOR
            . 'sessions';

        if (is_dir($sessionDirectory)) {
            @rmdir($sessionDirectory);
        }

        if (is_dir($this->directory)) {
            @rmdir($this->directory);
        }

        parent::tearDown();
    }

    public function test_no_inicia_metricas_si_estan_desactivadas(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $_ENV['PERFORMANCE_METRICS_ENABLED'] = 'false';

        $bootstrap = new PerformanceMetricsBootstrap();

        $lifecycle = $bootstrap->start(
            new Request(method: 'GET', path: '/api/health')
        );

        $this->assertNull($lifecycle);
        $this->assertNull(PerformanceMetricsContext::current());
    }

    public function test_no_inicia_metricas_para_rutas_fuera_de_la_api(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $_ENV['PERFORMANCE_METRICS_ENABLED'] = 'true';

        $bootstrap = new PerformanceMetricsBootstrap();

        $lifecycle = $bootstrap->start(
            new Request(method: 'GET', path: '/pagina')
        );

        $this->assertNull($lifecycle);
        $this->assertNull(PerformanceMetricsContext::current());
    }

    public function test_inicia_metricas_en_desarrollo_y_escribe_el_evento(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $_ENV['PERFORMANCE_METRICS_ENABLED'] = 'true';
        $_ENV['PERFORMANCE_METRICS_SLOW_SQL_MS'] = '75';

        $path = $this->directory
            . DIRECTORY_SEPARATOR
            . 'performance.jsonl';

        $sessions = new PerformanceMetricsSession(
            $this->directory . DIRECTORY_SEPARATOR . 'sessions'
        );
        $sessionId = $sessions->start();
        $bootstrap = new PerformanceMetricsBootstrap(
            new PerformanceMetricsLogger($path),
            $sessions
        );

        $lifecycle = $bootstrap->start(
            new Request(method: 'GET', path: '/api/propiedades')
        );

        $this->assertNotNull($lifecycle);
        $this->assertNotNull(PerformanceMetricsContext::current());

        $lifecycle->finish(200);

        $this->assertNull(PerformanceMetricsContext::current());

        $line = file_get_contents($path);
        $this->assertNotFalse($line);

        $event = json_decode(
            trim($line),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame('GET', $event['method']);
        $this->assertSame($sessionId, $event['session_id']);
        $this->assertSame(200, $event['status_code']);
        $this->assertSame(0, $event['sql']['query_count']);
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{32}$/',
            $event['request_id']
        );
    }

    public function test_no_inicia_metricas_fuera_de_desarrollo(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['PERFORMANCE_METRICS_ENABLED'] = 'true';

        $bootstrap = new PerformanceMetricsBootstrap();

        $lifecycle = $bootstrap->start(
            new Request(method: 'GET', path: '/api/health')
        );

        $this->assertNull($lifecycle);
        $this->assertNull(PerformanceMetricsContext::current());
    }

    public function test_informa_un_umbral_invalido_y_no_inicia_metricas(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $_ENV['PERFORMANCE_METRICS_ENABLED'] = 'true';
        $_ENV['PERFORMANCE_METRICS_SLOW_SQL_MS'] = '-1';

        $bootstrap = new PerformanceMetricsBootstrap();

        $lifecycle = $bootstrap->start(
            new Request(method: 'GET', path: '/api/health')
        );

        $this->assertNull($lifecycle);
        $this->assertNull(PerformanceMetricsContext::current());

        $errorLog = file_get_contents(
            $this->directory
                . DIRECTORY_SEPARATOR
                . 'php-error.log'
        );

        $this->assertNotFalse($errorLog);
        $this->assertStringContainsString(
            'No se pudo iniciar la instrumentación de rendimiento',
            $errorLog
        );
    }
}