<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use App\Cache\FileCache;
use PHPUnit\Framework\TestCase;
use App\Performance\PerformanceMetrics;
use App\Performance\PerformanceMetricsContext;

final class FileCacheTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'alquiler-cache-'
            . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->directory)) {
            return;
        }

        foreach (scandir($this->directory) ?: [] as $filename) {
            if ($filename === '.' || $filename === '..') {
                continue;
            }

            unlink($this->directory . DIRECTORY_SEPARATOR . $filename);
        }

        rmdir($this->directory);
    }

    public function test_reutiliza_una_entrada_vigente(): void
    {
        $cache = new FileCache($this->directory);
        $cargas = 0;
        $loader = static function () use (&$cargas): array {
            $cargas++;

            return ['items' => [['nombre' => 'Casa']], 'total' => 1];
        };

        $primeraRespuesta = $cache->remember('catalogo', 300, $loader);
        $segundaRespuesta = (new FileCache($this->directory))->remember(
            'catalogo',
            300,
            $loader
        );

        $this->assertSame($primeraRespuesta, $segundaRespuesta);
        $this->assertSame(1, $cargas);
    }

    public function test_vuelve_a_cargar_una_entrada_expirada(): void
    {
        $cache = new FileCache($this->directory);
        $cargas = 0;
        $loader = static function () use (&$cargas): array {
            $cargas++;

            return ['total' => $cargas];
        };

        $cache->remember('catalogo', 1, $loader);
        sleep(2);
        $respuesta = $cache->remember('catalogo', 300, $loader);

        $this->assertSame(['total' => 2], $respuesta);
        $this->assertSame(2, $cargas);
    }

    public function test_invalida_una_entrada(): void
    {
        $cache = new FileCache($this->directory);
        $cargas = 0;
        $loader = static function () use (&$cargas): array {
            $cargas++;

            return ['total' => $cargas];
        };

        $cache->remember('catalogo', 300, $loader);
        $cache->forget('catalogo');
        $respuesta = $cache->remember('catalogo', 300, $loader);

        $this->assertSame(['total' => 2], $respuesta);
        $this->assertSame(2, $cargas);
    }

    public function test_registra_aciertos_fallos_y_duracion_del_loader(): void
    {
        $metrics = new PerformanceMetrics(
            requestId: 'request-cache-1',
            method: 'GET',
            route: '/api/catalogos',
        );
        PerformanceMetricsContext::activate($metrics);

        try {
            $cache = new FileCache($this->directory);
            $loads = 0;
            $loader = static function () use (&$loads): array {
                $loads++;

                return ['items' => ['provincia']];
            };

            $first = $cache->remember('catalogo', 300, $loader);
            $second = $cache->remember('catalogo', 300, $loader);
            $event = $metrics->finish(200);
        } finally {
            PerformanceMetricsContext::clear();
        }

        $this->assertSame($first, $second);
        $this->assertSame(1, $loads);
        $this->assertSame(1, $event['cache']['miss_count']);
        $this->assertSame(1, $event['cache']['hit_count']);
        $this->assertGreaterThanOrEqual(0, $event['cache']['loader_ms']);
    }

    private function pathFor(string $directory, string $key): string
    {
        return rtrim($directory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . hash('sha256', $key)
            . '.json';
    }

    public function test_rechaza_ttl_no_positivo(): void
    {
        $cache = new FileCache($this->directory);

        $this->expectException(\InvalidArgumentException::class);
        $cache->remember('clave', 0, static fn (): array => []);
    }

    public function test_rechaza_clave_vacia(): void
    {
        $cache = new FileCache($this->directory);

        $this->expectException(\InvalidArgumentException::class);
        $cache->remember('', 300, static fn (): array => []);
    }

    public function test_rechaza_entrada_con_json_invalido(): void
    {
        $cache = new FileCache($this->directory);
        mkdir($this->directory, 0700, true);
        file_put_contents($this->pathFor($this->directory, 'clave'), '{invalido');

        $this->expectException(\RuntimeException::class);
        $cache->remember('clave', 300, static fn (): array => []);
    }

    public function test_rechaza_entrada_con_estructura_invalida(): void
    {
        $cache = new FileCache($this->directory);
        mkdir($this->directory, 0700, true);
        file_put_contents(
            $this->pathFor($this->directory, 'clave'),
            json_encode(['foo' => 'bar'])
        );

        $this->expectException(\RuntimeException::class);
        $cache->remember('clave', 300, static fn (): array => []);
    }

    public function test_rechaza_valor_no_codificable(): void
    {
        $cache = new FileCache($this->directory);

        $this->expectException(\RuntimeException::class);
        $cache->remember(
            'clave',
            300,
            static fn (): array => ['recurso' => fopen('php://memory', 'r')]
        );
    }

    public function test_falla_si_no_puede_crear_directorio(): void
    {
        $bloqueado = $this->directory . '-bloqueado';
        touch($bloqueado);

        try {
            $cache = new FileCache($bloqueado . DIRECTORY_SEPARATOR . 'cache');

            $this->expectException(\RuntimeException::class);
            $cache->remember('clave', 300, static fn (): array => []);
        } finally {
            @unlink($bloqueado);
        }
    }

    public function test_falla_si_bloqueo_es_directorio(): void
    {
        $cache = new FileCache($this->directory);
        mkdir($this->directory, 0700, true);
        $rutaBloqueo = $this->pathFor($this->directory, 'clave') . '.lock';
        mkdir($rutaBloqueo);

        try {
            $this->expectException(\RuntimeException::class);
            $cache->remember('clave', 300, static fn (): array => []);
        } finally {
            @rmdir($rutaBloqueo);
        }
    }

    public function test_falla_si_destino_es_directorio(): void
    {
        $cache = new FileCache($this->directory);
        mkdir($this->directory, 0700, true);
        $destino = $this->pathFor($this->directory, 'clave');
        mkdir($destino);

        try {
            $this->expectException(\RuntimeException::class);
            $cache->remember('clave', 300, static fn (): array => ['ok' => true]);
        } finally {
            @rmdir($destino);
        }
    }

    public function test_falla_si_no_puede_borrar_entrada_expirada(): void
    {
        $cache = new class ($this->directory) extends FileCache {
            protected function deleteFile(string $path): bool
            {
                return false;
            }
        };

        mkdir($this->directory, 0700, true);
        file_put_contents(
            $this->pathFor($this->directory, 'clave'),
            json_encode(['expires_at' => time() - 10, 'value' => ['x' => 1]])
        );

        $this->expectException(\RuntimeException::class);
        $cache->remember('clave', 300, static fn (): array => ['x' => 2]);
    }

    public function test_falla_si_no_puede_invalidar_entrada(): void
    {
        $cache = new class ($this->directory) extends FileCache {
            protected function deleteFile(string $path): bool
            {
                return false;
            }
        };

        mkdir($this->directory, 0700, true);
        file_put_contents(
            $this->pathFor($this->directory, 'clave'),
            json_encode(['expires_at' => time() + 300, 'value' => ['x' => 1]])
        );

        $this->expectException(\RuntimeException::class);
        $cache->forget('clave');
    }

    public function test_falla_si_no_puede_leer_entrada(): void
    {
        $cache = new class ($this->directory) extends FileCache {
            protected function readFileContents(string $path): string|false
            {
                return false;
            }
        };

        mkdir($this->directory, 0700, true);
        file_put_contents(
            $this->pathFor($this->directory, 'clave'),
            json_encode(['expires_at' => time() + 300, 'value' => ['x' => 1]])
        );

        $this->expectException(\RuntimeException::class);
        $cache->remember('clave', 300, static fn (): array => ['x' => 2]);
    }

    public function test_falla_si_no_puede_crear_archivo_temporal(): void
    {
        $cache = new class ($this->directory) extends FileCache {
            protected function createTempFile(string $directory, string $prefix): string|false
            {
                return false;
            }
        };

        $this->expectException(\RuntimeException::class);
        $cache->remember('clave', 300, static fn (): array => ['ok' => true]);
    }

    public function test_falla_si_no_puede_escribir_archivo_temporal(): void
    {
        $cache = new class ($this->directory) extends FileCache {
            protected function writeFileContents(string $path, string $contents): int|false
            {
                return false;
            }
        };

        $this->expectException(\RuntimeException::class);
        $cache->remember('clave', 300, static fn (): array => ['ok' => true]);
    }

    public function test_falla_si_no_puede_bloquear_entrada(): void
    {
        $cache = new class ($this->directory) extends FileCache {
            protected function acquireLock(mixed $lock): bool
            {
                return false;
            }
        };

        $this->expectException(\RuntimeException::class);
        $cache->remember('clave', 300, static fn (): array => ['ok' => true]);
    }
}
