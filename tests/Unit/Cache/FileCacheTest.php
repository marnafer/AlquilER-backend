<?php

declare(strict_types=1);

namespace Tests\Unit\Cache;

use App\Cache\FileCache;
use PHPUnit\Framework\TestCase;

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
}
