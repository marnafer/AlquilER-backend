<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Localidad;
use App\Models\Provincia;
use App\Repositories\LocalidadRepositoryInterface;
use App\Repositories\ProvinciaRepositoryInterface;
use App\Services\LocalidadService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class LocalidadServiceTest extends TestCase
{
    public function test_lista_localidades_y_devuelve_el_total(): void
    {
        $localidades = new Collection([
            new Localidad(['nombre' => 'La Plata']),
            new Localidad(['nombre' => 'Quilmes']),
        ]);

        $localidadRepository = $this->createMock(LocalidadRepositoryInterface::class);
        $provinciaRepository = $this->createMock(ProvinciaRepositoryInterface::class);

        $localidadRepository
            ->expects($this->once())
            ->method('all')
            ->willReturn($localidades);

        $service = (new LocalidadService($localidadRepository, $provinciaRepository))->listar();

        $this->assertSame($localidades, $service['items']);
        $this->assertSame(2, $service['total']);
    }

    public function test_obtener_devuelve_una_localidad_existente(): void
    {
        $localidad = new Localidad(['nombre' => 'La Plata']);
        $localidad->id = 1;

        $localidadRepository = $this->createMock(LocalidadRepositoryInterface::class);
        $provinciaRepository = $this->createMock(ProvinciaRepositoryInterface::class);

        $localidadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($localidad);

        $service = new LocalidadService($localidadRepository, $provinciaRepository);
        $resultado = $service->obtener(1);

        $this->assertSame($localidad, $resultado);
    }

    public function test_obtener_lanza_excepcion_si_no_existe(): void
    {
        $localidadRepository = $this->createMock(LocalidadRepositoryInterface::class);
        $provinciaRepository = $this->createMock(ProvinciaRepositoryInterface::class);
        $localidadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $service = new LocalidadService($localidadRepository, $provinciaRepository);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Localidad no encontrada');

        $service->obtener(1);
    }

    public function test_obtener_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $localidadRepository = $this->createMock(LocalidadRepositoryInterface::class);
        $provinciaRepository = $this->createMock(ProvinciaRepositoryInterface::class);
        $localidadRepository
            ->expects($this->never())
            ->method('findById');

        $service = new LocalidadService($localidadRepository, $provinciaRepository);

        $this->expectException(ValidationException::class);

        $service->obtener('abc');
    }

    public function test_crear_lanza_excepcion_si_los_datos_son_invalidos(): void
    {
        $localidadRepository = $this->createMock(LocalidadRepositoryInterface::class);
        $provinciaRepository = $this->createMock(ProvinciaRepositoryInterface::class);
        $localidadRepository
            ->expects($this->never())
            ->method('existsByNameInProvince');

        $service = new LocalidadService($localidadRepository, $provinciaRepository);

        $this->expectException(ValidationException::class);

        $service->crear([]);
    }

        public function test_crea_una_localidad(): void
    {
        $localidad = new Localidad([
            'nombre' => 'La Plata',
            'codigo_postal' => '1900',
            'provincia_id' => 1,
        ]);

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $provinciaRepository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $provinciaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(new Provincia());

        $localidadRepository
            ->expects($this->once())
            ->method('existsByNameInProvince')
            ->with('La Plata', 1)
            ->willReturn(false);

        $localidadRepository
            ->expects($this->once())
            ->method('create')
            ->with([
                'id' => null,
                'nombre' => 'La Plata',
                'codigo_postal' => '1900',
                'provincia_id' => 1,
            ])
            ->willReturn($localidad);

        $service = new LocalidadService(
            $localidadRepository,
            $provinciaRepository
        );

        $resultado = $service->crear([
            'nombre' => ' la   plata ',
            'codigo_postal' => '1900',
            'provincia_id' => 1,
        ]);

        $this->assertSame($localidad, $resultado);
    }

    public function test_crear_lanza_excepcion_si_la_provincia_no_existe(): void
    {
        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $provinciaRepository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $provinciaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $localidadRepository
            ->expects($this->never())
            ->method('existsByNameInProvince');

        $localidadRepository
            ->expects($this->never())
            ->method('create');

        $service = new LocalidadService(
            $localidadRepository,
            $provinciaRepository
        );

        $this->expectException(ValidationException::class);

        $service->crear([
            'nombre' => 'La Plata',
            'codigo_postal' => '1900',
            'provincia_id' => 99,
        ]);
    }

    public function test_crear_lanza_excepcion_si_la_localidad_ya_existe(): void
    {
        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $provinciaRepository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $provinciaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(new Provincia());

        $localidadRepository
            ->expects($this->once())
            ->method('existsByNameInProvince')
            ->with('La Plata', 1)
            ->willReturn(true);

        $localidadRepository
            ->expects($this->never())
            ->method('create');

        $service = new LocalidadService(
            $localidadRepository,
            $provinciaRepository
        );

        $this->expectException(ConflictException::class);

        $service->crear([
            'nombre' => 'La Plata',
            'codigo_postal' => '1900',
            'provincia_id' => 1,
        ]);
    }

    public function test_actualiza_una_localidad(): void
    {
        $localidad = new Localidad([
            'nombre' => 'La Plata',
            'codigo_postal' => '1900',
            'provincia_id' => 1,
        ]);
        $localidad->id = 1;

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $provinciaRepository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $localidadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($localidad);

        $provinciaRepository
            ->expects($this->never())
            ->method('findById');

        $localidadRepository
            ->expects($this->once())
            ->method('existsByNameInProvince')
            ->with('Quilmes', 1, 1)
            ->willReturn(false);

        $localidadRepository
            ->expects($this->once())
            ->method('update')
            ->with($localidad, [
                'nombre' => 'Quilmes',
                'codigo_postal' => '1878',
            ])
            ->willReturn(true);

        $service = new LocalidadService(
            $localidadRepository,
            $provinciaRepository
        );

        $service->actualizar(1, [
            'nombre' => ' quilmes ',
            'codigo_postal' => '1878',
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_actualizar_lanza_excepcion_si_la_provincia_no_existe(): void
    {
        $localidad = new Localidad([
            'nombre' => 'La Plata',
            'codigo_postal' => '1900',
            'provincia_id' => 1,
        ]);
        $localidad->id = 1;

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $provinciaRepository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $localidadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($localidad);

        $provinciaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $localidadRepository
            ->expects($this->never())
            ->method('existsByNameInProvince');

        $localidadRepository
            ->expects($this->never())
            ->method('update');

        $service = new LocalidadService(
            $localidadRepository,
            $provinciaRepository
        );

        $this->expectException(ValidationException::class);

        $service->actualizar(1, [
            'provincia_id' => 99,
        ]);
    }

    public function test_actualizar_lanza_excepcion_si_el_nombre_ya_existe(): void
    {
        $localidad = new Localidad([
            'nombre' => 'La Plata',
            'codigo_postal' => '1900',
            'provincia_id' => 1,
        ]);
        $localidad->id = 1;

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $provinciaRepository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $localidadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($localidad);

        $provinciaRepository
            ->expects($this->never())
            ->method('findById');

        $localidadRepository
            ->expects($this->once())
            ->method('existsByNameInProvince')
            ->with('Quilmes', 1, 1)
            ->willReturn(true);

        $localidadRepository
            ->expects($this->never())
            ->method('update');

        $service = new LocalidadService(
            $localidadRepository,
            $provinciaRepository
        );

        $this->expectException(ConflictException::class);

        $service->actualizar(1, [
            'nombre' => 'Quilmes',
        ]);
    }

    public function test_elimina_una_localidad_sin_propiedades(): void
    {
        $localidad = new Localidad(['nombre' => 'La Plata']);
        $localidad->id = 1;

        $localidadRepository = $this->createMock(LocalidadRepositoryInterface::class);
        $localidadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($localidad);
        $localidadRepository
            ->expects($this->once())
            ->method('hasProperties')
            ->with($localidad)
            ->willReturn(false);
        $localidadRepository
            ->expects($this->once())
            ->method('delete')
            ->with($localidad);

        $provinciaRepository = $this->createMock(ProvinciaRepositoryInterface::class);
        $service = new LocalidadService($localidadRepository, $provinciaRepository);
        $service->eliminar(1);

        $this->addToAssertionCount(1);
    }

    public function test_eliminar_lanza_excepcion_si_tiene_propiedades(): void
    {
        $localidad = new Localidad(['nombre' => 'La Plata']);
        $localidad->id = 1;

        $localidadRepository = $this->createMock(LocalidadRepositoryInterface::class);
        $localidadRepository
            ->method('findById')
            ->with(1)
            ->willReturn($localidad);
        $localidadRepository
            ->expects($this->once())
            ->method('hasProperties')
            ->with($localidad)
            ->willReturn(true);
        $localidadRepository
            ->expects($this->never())
            ->method('delete');

        $provinciaRepository = $this->createMock(ProvinciaRepositoryInterface::class);
        $service = new LocalidadService($localidadRepository, $provinciaRepository);

        $this->expectException(ConflictException::class);

        $service->eliminar(1);
    }

    public function test_restaura_una_localidad_eliminada(): void
    {
        $localidad = new Localidad([
            'nombre' => 'La Plata',
            'provincia_id' => 1,
        ]);
        $localidad->id = 1;

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $provinciaRepository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $localidadRepository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($localidad);

        $localidadRepository
            ->expects($this->once())
            ->method('existsByNameInProvince')
            ->with('La Plata', 1, 1)
            ->willReturn(false);

        $localidadRepository
            ->expects($this->once())
            ->method('restore')
            ->with($localidad)
            ->willReturn(true);

        $service = new LocalidadService(
            $localidadRepository,
            $provinciaRepository
        );

        $service->restaurar(1);

        $this->addToAssertionCount(1);
    }

    public function test_restaurar_lanza_excepcion_si_no_existe(): void
    {
        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $provinciaRepository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $localidadRepository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn(null);

        $localidadRepository
            ->expects($this->never())
            ->method('restore');

        $service = new LocalidadService(
            $localidadRepository,
            $provinciaRepository
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Localidad eliminada no encontrada');

        $service->restaurar(1);
    }

    public function test_restaurar_lanza_excepcion_si_el_nombre_ya_esta_activo(): void
    {
        $localidad = new Localidad([
            'nombre' => 'La Plata',
            'provincia_id' => 1,
        ]);
        $localidad->id = 1;

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $provinciaRepository = $this->createMock(
            ProvinciaRepositoryInterface::class
        );

        $localidadRepository
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($localidad);

        $localidadRepository
            ->expects($this->once())
            ->method('existsByNameInProvince')
            ->with('La Plata', 1, 1)
            ->willReturn(true);

        $localidadRepository
            ->expects($this->never())
            ->method('restore');

        $service = new LocalidadService(
            $localidadRepository,
            $provinciaRepository
        );

        $this->expectException(ConflictException::class);

        $service->restaurar(1);
    }
}