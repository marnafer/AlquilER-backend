<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Categoria;
use App\Repositories\CategoriaRepositoryInterface;
use App\Services\CategoriaService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class CategoriaServiceTest extends TestCase
{
    public function test_lista_categorias_y_devuelve_el_total(): void
    {
        $categoria = new Categoria(['nombre' => 'Casa']);
        $categorias = new Collection([$categoria]);

        $repository = $this->createMock(CategoriaRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('all')
            ->willReturn($categorias);

        $service = new CategoriaService($repository);

        $resultado = $service->listar();

        $this->assertSame($categorias, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_obtiene_una_categoria_existente(): void
    {
        $categoria = new Categoria(['nombre' => 'Casa']);
        $categoria->id = 1;

        $repository = $this->createMock(CategoriaRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($categoria);

        $service = new CategoriaService($repository);

        $this->assertSame($categoria, $service->obtener(1));
    }

    public function test_obtener_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(CategoriaRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $service = new CategoriaService($repository);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Categoría no encontrada');

        $service->obtener(1);
    }

    public function test_obtener_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(CategoriaRepositoryInterface::class);

        $repository
            ->expects($this->never())
            ->method('findById');

        $service = new CategoriaService($repository);

        $this->expectException(ValidationException::class);

        $service->obtener('abc');
    }

    public function test_crea_una_categoria(): void
    {
        $categoria = new Categoria(['nombre' => 'Casa']);

        $repository = $this->createMock(CategoriaRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Casa')
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('create')
            ->with([
                'id' => null,
                'nombre' => 'Casa',
            ])
            ->willReturn($categoria);

        $service = new CategoriaService($repository);

        $resultado = $service->crear([
            'nombre' => ' casa ',
        ]);

        $this->assertSame($categoria, $resultado);
    }

    public function test_crear_lanza_excepcion_si_el_nombre_ya_existe(): void
    {
        $repository = $this->createMock(CategoriaRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Casa')
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('create');

        $service = new CategoriaService($repository);

        $this->expectException(ConflictException::class);

        $service->crear(['nombre' => 'Casa']);
    }

    public function test_elimina_una_categoria_sin_propiedades(): void
    {
        $categoria = new Categoria(['nombre' => 'Casa']);
        $categoria->id = 1;

        $repository = $this->createMock(CategoriaRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($categoria);

        $repository
            ->expects($this->once())
            ->method('hasProperties')
            ->with($categoria)
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('delete')
            ->with($categoria)
            ->willReturn(true);

        $service = new CategoriaService($repository);

        $service->eliminar(1);

        $this->addToAssertionCount(1);
    }

    public function test_eliminar_lanza_excepcion_si_tiene_propiedades(): void
    {
        $categoria = new Categoria(['nombre' => 'Casa']);
        $categoria->id = 1;

        $repository = $this->createMock(CategoriaRepositoryInterface::class);

        $repository
            ->method('findById')
            ->willReturn($categoria);

        $repository
            ->expects($this->once())
            ->method('hasProperties')
            ->with($categoria)
            ->willReturn(true);

        $repository
            ->expects($this->never())
            ->method('delete');

        $service = new CategoriaService($repository);

        $this->expectException(ConflictException::class);

        $service->eliminar(1);
    }

    public function test_actualiza_una_categoria(): void
    {
        $categoria = new Categoria(['nombre' => 'Casa']);
        $categoria->id = 1;

        $repository = $this->createMock(CategoriaRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($categoria);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Departamento', 1)
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('update')
            ->with($categoria, ['nombre' => 'Departamento'])
            ->willReturn(true);

        $service = new CategoriaService($repository);

        $service->actualizar(1, [
            'nombre' => ' departamento ',
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_actualizar_lanza_excepcion_si_no_hay_campos(): void
    {
        $categoria = new Categoria(['nombre' => 'Casa']);
        $categoria->id = 1;

        $repository = $this->createMock(
            CategoriaRepositoryInterface::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($categoria);

        $service = new CategoriaService($repository);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'Debe enviar al menos un campo para actualizar'
        );

        $service->actualizar(1, []);
    }

    public function test_restaurar_una_categoria_eliminada(): void
    {
        $categoria = new Categoria(['nombre' => 'Casa']);
        $categoria->id = 1;

        $repository = $this->createMock(CategoriaRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($categoria);

        $repository
            ->expects($this->once())
            ->method('existsByName')
            ->with('Casa', 1)
            ->willReturn(false);

        $repository
            ->expects($this->once())
            ->method('restore')
            ->with($categoria)
            ->willReturn(true);

        $service = new CategoriaService($repository);

        $service->restaurar(1);

        $this->addToAssertionCount(1);
    }
}