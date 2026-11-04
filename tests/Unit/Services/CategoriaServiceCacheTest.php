<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Cache\FileCache;
use App\Models\Categoria;
use App\Repositories\CategoriaRepositoryInterface;
use App\Services\CategoriaService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class CategoriaServiceCacheTest extends TestCase
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

    public function test_mutar_categoria_invalida_el_listado_cacheado(): void
    {
        $categoriaInicial = new Categoria(['nombre' => 'Casa']);
        $categoriaNueva = new Categoria(['nombre' => 'Departamento']);
        $repository = $this->createMock(CategoriaRepositoryInterface::class);
        $repository
            ->expects($this->exactly(2))
            ->method('all')
            ->willReturnOnConsecutiveCalls(
                new Collection([$categoriaInicial]),
                new Collection([$categoriaInicial, $categoriaNueva])
            );
        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Departamento')
            ->willReturn(false);
        $repository
            ->expects($this->once())
            ->method('create')
            ->with(['nombre' => 'Departamento'])
            ->willReturn($categoriaNueva);

        $service = new CategoriaService(
            $repository,
            new FileCache($this->directory)
        );

        $this->assertSame(1, $service->listar()['total']);
        $service->crear(['nombre' => 'Departamento']);
        $resultadoActualizado = $service->listar();

        $this->assertSame(2, $resultadoActualizado['total']);
        $this->assertSame(
            ['Casa', 'Departamento'],
            array_column($resultadoActualizado['items'], 'nombre')
        );
    }
}
