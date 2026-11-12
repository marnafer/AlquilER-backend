<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Cache\FileCache;
use App\Models\Localidad;
use App\Repositories\LocalidadRepositoryInterface;
use App\Repositories\ProvinciaRepositoryInterface;
use App\Services\LocalidadService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class LocalidadServiceCoverageTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'alquiler-localidad-cache-' . bin2hex(random_bytes(6));
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
        $localidades = new Collection([
            new Localidad(['nombre' => 'La Plata']),
        ]);

        $localidadRepository = $this->createMock(LocalidadRepositoryInterface::class);
        $provinciaRepository = $this->createMock(ProvinciaRepositoryInterface::class);

        $localidadRepository
            ->expects($this->once())
            ->method('all')
            ->willReturn($localidades);

        $service = new LocalidadService(
            $localidadRepository,
            $provinciaRepository,
            new FileCache($this->directory)
        );

        $resultado = $service->listar();

        $this->assertSame(1, $resultado['total']);
    }
}