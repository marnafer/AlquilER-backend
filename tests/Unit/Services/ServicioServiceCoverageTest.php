<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Cache\FileCache;
use App\Models\Servicio;
use App\Repositories\ServicioRepositoryInterface;
use App\Services\ServicioService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class ServicioServiceCoverageTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'alquiler-servicio-cache-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->directory) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            @unlink($this->directory . DIRECTORY_SEPARATOR . $file);
        }
        @rmdir($this->directory);
        parent::tearDown();
    }

    public function test_listar_usa_cache_cuando_no_hay_filtros(): void
    {
        $servicios = new Collection([
            new Servicio(['nombre' => 'WiFi']),
        ]);

        $repository = $this->createMock(ServicioRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('all')
            ->willReturn($servicios);

        $service = new ServicioService(
            $repository,
            new FileCache($this->directory)
        );

        $resultado = $service->listar();

        $this->assertSame(1, $resultado['total']);
    }
}