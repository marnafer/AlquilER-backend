<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Categoria;
use App\Models\Localidad;
use App\Models\Propiedad;
use App\Repositories\CategoriaRepositoryInterface;
use App\Repositories\LocalidadRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Services\LogActividadService;
use App\Services\PropiedadService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class PropiedadServiceTest extends TestCase
{
    public function test_lista_propiedades_y_devuelve_el_total(): void
    {
        $propiedades = new Collection([
            new Propiedad(['titulo' => 'Casa']),
            new Propiedad(['titulo' => 'Departamento']),
        ]);
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('all')
            ->willReturn($propiedades);

        $resultado = $this->crearServicio($repository)->listar();

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(2, $resultado['total']);
    }

    public function test_obtener_devuelve_una_propiedad_existente(): void
    {
        $propiedad = $this->propiedad();
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $this->assertSame(
            $propiedad,
            $this->crearServicio($repository)->obtener(1)
        );
    }

    public function test_obtener_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->never())->method('findById');

        $this->expectException(ValidationException::class);

        $this->crearServicio($repository)->obtener('abc');
    }

    public function test_obtener_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Propiedad no encontrada');

        $this->crearServicio($repository)->obtener(1);
    }

    public function test_crea_una_propiedad_y_registra_la_actividad(): void
    {
        $propiedad = $this->propiedad();
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $categoriaRepository = $this->createMock(CategoriaRepositoryInterface::class);
        $localidadRepository = $this->createMock(LocalidadRepositoryInterface::class);
        $logService = $this->createMock(LogActividadService::class);

        $categoriaRepository->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn(new Categoria());
        $localidadRepository->expects($this->once())
            ->method('findById')
            ->with(3)
            ->willReturn(new Localidad());
        $logService->expects($this->once())
            ->method('registrar')
            ->with(7, 'Creación de propiedad');
        $repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data): bool {
                return $data['titulo'] === 'Casa amplia'
                    && $data['precio'] === 125000.5
                    && $data['expensas'] == 0
                    && $data['categoria_id'] === 2
                    && $data['localidad_id'] === 3
                    && $data['usuario_id'] === 7;
            }))
            ->willReturn($propiedad);

        $resultado = $this->crearServicio(
            $repository,
            $logService,
            $categoriaRepository,
            $localidadRepository
        )->crear($this->datosPropiedad(), 7);

        $this->assertSame($propiedad, $resultado);
    }

    public function test_crear_lanza_excepcion_si_los_datos_son_invalidos(): void
    {
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->never())->method('create');
        $categoriaRepository = $this->createMock(CategoriaRepositoryInterface::class);
        $categoriaRepository->expects($this->never())->method('findById');
        $logService = $this->createMock(LogActividadService::class);
        $logService->expects($this->never())->method('registrar');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            $repository,
            $logService,
            $categoriaRepository
        )->crear([], 7);
    }

    public function test_crear_lanza_excepcion_si_no_existe_la_categoria(): void
    {
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->never())->method('create');
        $categoriaRepository = $this->createMock(CategoriaRepositoryInterface::class);
        $categoriaRepository->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn(null);
        $logService = $this->createMock(LogActividadService::class);
        $logService->expects($this->never())->method('registrar');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            $repository,
            $logService,
            $categoriaRepository
        )->crear($this->datosPropiedad(), 7);
    }

    public function test_crear_lanza_excepcion_si_no_existe_la_localidad(): void
    {
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->never())->method('create');
        $categoriaRepository = $this->createMock(CategoriaRepositoryInterface::class);
        $categoriaRepository->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn(new Categoria());
        $localidadRepository = $this->createMock(LocalidadRepositoryInterface::class);
        $localidadRepository->expects($this->once())
            ->method('findById')
            ->with(3)
            ->willReturn(null);
        $logService = $this->createMock(LogActividadService::class);
        $logService->expects($this->never())->method('registrar');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            $repository,
            $logService,
            $categoriaRepository,
            $localidadRepository
        )->crear($this->datosPropiedad(), 7);
    }

    public function test_actualiza_una_propiedad_y_registra_la_actividad(): void
    {
        $propiedad = $this->propiedad();
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);
        $repository->expects($this->once())
            ->method('update')
            ->with($propiedad, $this->callback(function (array $data): bool {
                return $data['titulo'] === 'Casa renovada'
                    && $data['precio'] === 150000.0
                    && $data['categoria_id'] === 2
                    && $data['localidad_id'] === 3;
            }))
            ->willReturn(true);
        $categoriaRepository = $this->createMock(CategoriaRepositoryInterface::class);
        $categoriaRepository->expects($this->once())
            ->method('findById')->with(2)->willReturn(new Categoria());
        $localidadRepository = $this->createMock(LocalidadRepositoryInterface::class);
        $localidadRepository->expects($this->once())
            ->method('findById')->with(3)->willReturn(new Localidad());
        $logService = $this->createMock(LogActividadService::class);
        $logService->expects($this->once())
            ->method('registrar')
            ->with(7, 'Actualización de propiedad');

        $this->crearServicio(
            $repository,
            $logService,
            $categoriaRepository,
            $localidadRepository
        )->actualizar(7, 1, 1, [
            'titulo' => ' Casa renovada ',
            'precio' => '150000',
        ]);
    }

    public function test_actualizar_lanza_excepcion_si_no_tiene_permiso(): void
    {
        $propiedad = $this->propiedad();
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')->with(1)->willReturn($propiedad);
        $repository->expects($this->never())->method('update');

        $this->expectException(ForbiddenException::class);

        $this->crearServicio($repository)->actualizar(8, 1, 1, [
            'titulo' => 'Casa renovada',
        ]);
    }

    public function test_actualizar_lanza_excepcion_si_no_hay_campos(): void
    {
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')->with(1)->willReturn($this->propiedad());

        $this->expectException(BadRequestException::class);

        $this->crearServicio($repository)->actualizar(7, 1, 1, []);
    }

    public function test_actualizar_lanza_excepcion_si_no_hay_campos_actualizables(): void
    {
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')->with(1)->willReturn($this->propiedad());

        $this->expectException(BadRequestException::class);

        $this->crearServicio($repository)->actualizar(7, 1, 1, [
            'usuario_id' => 99,
        ]);
    }

    public function test_elimina_una_propiedad_y_registra_la_actividad(): void
    {
        $propiedad = $this->propiedad();
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')->with(1)->willReturn($propiedad);
        $repository->expects($this->once())
            ->method('delete')->with($propiedad)->willReturn(true);
        $logService = $this->createMock(LogActividadService::class);
        $logService->expects($this->once())
            ->method('registrar')->with(7, 'Eliminación de propiedad');

        $this->crearServicio($repository, $logService)->eliminar(7, 1, 1);

        $this->addToAssertionCount(1);
    }

    public function test_eliminar_lanza_excepcion_si_no_tiene_permiso(): void
    {
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findById')->with(1)->willReturn($this->propiedad());
        $repository->expects($this->never())->method('delete');

        $this->expectException(ForbiddenException::class);

        $this->crearServicio($repository)->eliminar(8, 1, 1);
    }

    public function test_restaura_una_propiedad_eliminada(): void
    {
        $propiedad = $this->propiedadEliminada();
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findDeletedById')->with(1)->willReturn($propiedad);
        $repository->expects($this->once())
            ->method('restore')->with($propiedad)->willReturn(true);
        $logService = $this->createMock(LogActividadService::class);
        $logService->expects($this->once())
            ->method('registrar')->with(7, 'Restauración de propiedad');

        $this->crearServicio($repository, $logService)->restaurar(7, 1, 1);

        $this->addToAssertionCount(1);
    }

    public function test_restaurar_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findDeletedById')->with(1)->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->crearServicio($repository)->restaurar(7, 1, 1);
    }

    public function test_restaurar_lanza_excepcion_si_no_tiene_permiso(): void
    {
        $propiedad = $this->propiedadEliminada();
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findDeletedById')->with(1)->willReturn($propiedad);
        $repository->expects($this->never())->method('restore');

        $this->expectException(ForbiddenException::class);

        $this->crearServicio($repository)->restaurar(8, 1, 1);
    }

    public function test_restaurar_lanza_excepcion_si_la_propiedad_no_esta_eliminada(): void
    {
        $repository = $this->createMock(PropiedadRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findDeletedById')->with(1)->willReturn($this->propiedad());
        $repository->expects($this->never())->method('restore');

        $this->expectException(BadRequestException::class);

        $this->crearServicio($repository)->restaurar(7, 1, 1);
    }

    private function crearServicio(
        ?PropiedadRepositoryInterface $repository = null,
        ?LogActividadService $logService = null,
        ?CategoriaRepositoryInterface $categoriaRepository = null,
        ?LocalidadRepositoryInterface $localidadRepository = null
    ): PropiedadService {
        return new PropiedadService(
            $repository ?? $this->createMock(PropiedadRepositoryInterface::class),
            $logService ?? $this->createMock(LogActividadService::class),
            $categoriaRepository ?? $this->createMock(CategoriaRepositoryInterface::class),
            $localidadRepository ?? $this->createMock(LocalidadRepositoryInterface::class)
        );
    }

    private function propiedad(): Propiedad
    {
        $propiedad = new Propiedad([
            'titulo' => 'Casa original',
            'descripcion' => 'Una casa cómoda',
            'precio' => 125000,
            'expensas' => 0,
            'direccion' => 'Calle 123',
            'cantidad_ambientes' => 3,
            'cantidad_dormitorios' => 2,
            'cantidad_banos' => 1,
            'capacidad' => 4,
            'disponible' => 1,
            'categoria_id' => 2,
            'localidad_id' => 3,
            'usuario_id' => 7,
        ]);
        $propiedad->id = 1;

        return $propiedad;
    }

    private function propiedadEliminada(): Propiedad
    {
        $propiedad = $this->getMockBuilder(Propiedad::class)
            ->onlyMethods(['getAttribute'])
            ->getMock();
        $propiedad->id = 1;
        $propiedad->method('getAttribute')
            ->willReturnCallback(function (string $attribute) {
                return match ($attribute) {
                    'usuario_id' => 7,
                    'deleted_at' => '2026-01-01 00:00:00',
                    default => null,
                };
            });

        return $propiedad;
    }

    private function datosPropiedad(): array
    {
        return [
            'titulo' => 'Casa amplia',
            'descripcion' => 'Una casa cómoda',
            'precio' => '125000,50',
            'direccion' => 'Calle 123',
            'cantidad_ambientes' => 3,
            'cantidad_dormitorios' => 2,
            'cantidad_banos' => 1,
            'capacidad' => 4,
            'categoria_id' => 2,
            'localidad_id' => 3,
        ];
    }
}