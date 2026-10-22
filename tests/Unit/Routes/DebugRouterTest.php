<?php
declare(strict_types=1);

namespace Tests\Unit\Routes;

use PHPUnit\Framework\TestCase;

final class DebugRouterTest extends TestCase
{
    private function ejecutar(array $env): array
    {
        $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
        $srcPath = dirname(__DIR__, 3) . '/src/';

        $script = tempnam(sys_get_temp_dir(), 'debug_router_');
        file_put_contents($script, "<?php\n"
            . "require getenv('ALQUILER_AUTOLOAD');\n"
            . "define('SRC_PATH', getenv('ALQUILER_SRC_PATH'));\n"
            . "\$_ENV['APP_ENV'] = getenv('APP_ENV') ?: 'production';\n"
            . "\$_SERVER['REQUEST_METHOD'] = getenv('REQUEST_METHOD') ?: 'GET';\n"
            . "\$_SERVER['REQUEST_URI'] = getenv('REQUEST_URI') ?: '/';\n"
            . "require SRC_PATH . 'Routes/debug_router.php';\n");

        $env = array_merge([
            'ALQUILER_AUTOLOAD' => $autoload,
            'ALQUILER_SRC_PATH' => $srcPath,
            'APP_ENV' => 'production',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ], $env);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(PHP_BINARY . ' ' . escapeshellarg($script), $descriptors, $pipes, null, $env);
        $this->assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        unlink($script);

        return [$exitCode, $stdout, $stderr];
    }

    public function test_debug_router_responde_404_en_produccion(): void
    {
        [$exitCode, $stdout, $stderr] = $this->ejecutar(['APP_ENV' => 'production']);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Not Found', $stdout);
        $this->assertSame('', $stderr);
    }

    public function test_debug_router_stats_en_desarrollo(): void
    {
        [$exitCode, $stdout, $stderr] = $this->ejecutar([
            'APP_ENV' => 'development',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/debug/stats',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('', $stderr);
    }

    public function test_debug_router_logs_en_desarrollo(): void
    {
        [$exitCode, $stdout, $stderr] = $this->ejecutar([
            'APP_ENV' => 'development',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/debug/logs',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('', $stderr);
    }

    public function test_debug_router_clear_log_en_desarrollo(): void
    {
        [$exitCode, $stdout, $stderr] = $this->ejecutar([
            'APP_ENV' => 'development',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/debug/clear-log',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('', $stderr);
    }

    public function test_debug_router_test_db_en_desarrollo(): void
    {
        [$exitCode, $stdout, $stderr] = $this->ejecutar([
            'APP_ENV' => 'development',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/debug/test-db',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('', $stderr);
    }

    public function test_debug_router_phpinfo_en_desarrollo(): void
    {
        [$exitCode, $stdout, $stderr] = $this->ejecutar([
            'APP_ENV' => 'development',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/debug/phpinfo',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('', $stderr);
    }

    public function test_debug_router_directo_404_en_produccion(): void
    {
        if (!defined('SRC_PATH')) {
            define('SRC_PATH', dirname(__DIR__, 3) . '/src/');
        }
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/debug/stats';

        $handled = require SRC_PATH . 'Routes/debug_router.php';

        $this->assertTrue($handled);
    }

    public function test_debug_router_directo_stats_en_desarrollo(): void
    {
        if (!defined('SRC_PATH')) {
            define('SRC_PATH', dirname(__DIR__, 3) . '/src/');
        }
        $_ENV['APP_ENV'] = 'development';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/debug/stats';

        $handled = require SRC_PATH . 'Routes/debug_router.php';

        $this->assertTrue($handled);
    }

    public function test_debug_router_directo_logs_en_desarrollo(): void
    {
        if (!defined('SRC_PATH')) {
            define('SRC_PATH', dirname(__DIR__, 3) . '/src/');
        }
        $_ENV['APP_ENV'] = 'development';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/debug/logs';

        $handled = require SRC_PATH . 'Routes/debug_router.php';

        $this->assertTrue($handled);
    }

    public function test_debug_router_directo_clear_log_en_desarrollo(): void
    {
        if (!defined('SRC_PATH')) {
            define('SRC_PATH', dirname(__DIR__, 3) . '/src/');
        }
        $_ENV['APP_ENV'] = 'development';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/debug/clear-log';

        $handled = require SRC_PATH . 'Routes/debug_router.php';

        $this->assertTrue($handled);
    }

    public function test_debug_router_directo_test_db_en_desarrollo(): void
    {
        if (!defined('SRC_PATH')) {
            define('SRC_PATH', dirname(__DIR__, 3) . '/src/');
        }
        $_ENV['APP_ENV'] = 'development';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/debug/test-db';

        $handled = require SRC_PATH . 'Routes/debug_router.php';

        $this->assertTrue($handled);
    }

    public function test_debug_router_directo_phpinfo_en_desarrollo(): void
    {
        if (!defined('SRC_PATH')) {
            define('SRC_PATH', dirname(__DIR__, 3) . '/src/');
        }
        $_ENV['APP_ENV'] = 'development';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/debug/phpinfo';

        $handled = require SRC_PATH . 'Routes/debug_router.php';

        $this->assertTrue($handled);
    }

    public function test_debug_router_directo_sin_ruta_coincidente(): void
    {
        if (!defined('SRC_PATH')) {
            define('SRC_PATH', dirname(__DIR__, 3) . '/src/');
        }
        $_ENV['APP_ENV'] = 'development';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/debug/inexistente';

        $handled = require SRC_PATH . 'Routes/debug_router.php';

        $this->assertFalse($handled);
    }
}
