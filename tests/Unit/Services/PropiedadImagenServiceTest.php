<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Propiedad;
use App\Models\PropiedadImagen;
use App\Repositories\PropiedadImagenRepositoryInterface;
use App\Services\LogActividadService;
use App\Services\PropiedadImagenService;
use App\Services\PropiedadService;
use App\Services\FileUploaderInterface;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class PropiedadImagenServiceTest extends TestCase
{
    public function test_lista_todas_las_imagenes_y_devuelve_el_total(): void
    {
        $imagenes = new Collection([
            new PropiedadImagen(['ruta' => '/uploads/uno.jpg']),
            new PropiedadImagen(['ruta' => '/uploads/dos.jpg']),
        ]);

        $repository = $this->createMock(PropiedadImagenRepositoryInterface::class);
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $fileUploader = $this->createMock(FileUploaderInterface::class);

        $repository
            ->expects($this->once())
            ->method('all')
            ->willReturn($imagenes);

        $resultado = (new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $fileUploader
        ))->listar();

        $this->assertSame($imagenes, $resultado['items']);
        $this->assertSame(2, $resultado['total']);
    }

    public function test_lista_una_imagen_por_id(): void
    {
        $imagen = new PropiedadImagen(['ruta' => '/uploads/uno.jpg']);
        $imagen->id = 1;

        $repository = $this->createMock(PropiedadImagenRepositoryInterface::class);
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $fileUploader = $this->createMock(FileUploaderInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);

        $resultado = (new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $fileUploader
        ))->listar(1);

        $this->assertSame([$imagen], $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(PropiedadImagenRepositoryInterface::class);
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $fileUploader = $this->createMock(FileUploaderInterface::class);

        $repository
            ->expects($this->never())
            ->method('findById');

        $service = new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $fileUploader
        );

        $this->expectException(ValidationException::class);

        $service->listar('abc');
    }

    public function test_obtener_devuelve_una_imagen_existente(): void
    {
        $imagen = new PropiedadImagen(['ruta' => '/uploads/uno.jpg']);
        $imagen->id = 1;

        $repository = $this->createMock(PropiedadImagenRepositoryInterface::class);
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $fileUploader = $this->createMock(FileUploaderInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);

        $resultado = (new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $fileUploader
        ))->obtener(1);

        $this->assertSame($imagen, $resultado);
    }

    public function test_obtener_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(PropiedadImagenRepositoryInterface::class);
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $fileUploader = $this->createMock(FileUploaderInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $service = new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $fileUploader
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Imagen no encontrada');

        $service->obtener(1);
    }

    public function test_crear_lanza_excepcion_si_no_se_envia_una_imagen_valida(): void
    {
        $repository = $this->createMock(PropiedadImagenRepositoryInterface::class);
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $fileUploader = $this->createMock(FileUploaderInterface::class);

        $propiedadService
            ->expects($this->never())
            ->method('obtener');
        $repository
            ->expects($this->never())
            ->method('create');

        $service = new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $fileUploader
        );

        $this->expectException(ValidationException::class);

        $service->crear(
            ['propiedad_id' => 1],
            [],
            (object) ['sub' => 1, 'rol_id' => 2]
        );
    }

    public function test_establece_una_imagen_como_principal(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $imagen = $this->getMockBuilder(PropiedadImagen::class)
            ->onlyMethods(['refresh'])
            ->getMock();
        $imagen->id = 1;
        $imagen->propiedad_id = 3;
        $imagen->setRelation('propiedad', $propiedad);
        $imagen
            ->expects($this->once())
            ->method('refresh')
            ->willReturn($imagen);

        $repository = $this->createMock(PropiedadImagenRepositoryInterface::class);
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $fileUploader = $this->createMock(FileUploaderInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);
        $repository
            ->expects($this->once())
            ->method('clearPrincipalByPropiedadId')
            ->with(3)
            ->willReturn(true);
        $repository
            ->expects($this->once())
            ->method('setPrincipal')
            ->with($imagen)
            ->willReturn(true);

        $resultado = (new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $fileUploader
        ))->establecerPrincipal(1, (object) ['sub' => 7, 'rol_id' => 1]);

        $this->assertSame($imagen, $resultado);
    }

    public function test_establecer_principal_lanza_excepcion_sin_permiso(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $imagen = new PropiedadImagen();
        $imagen->id = 1;
        $imagen->propiedad_id = 3;
        $imagen->setRelation('propiedad', $propiedad);

        $repository = $this->createMock(PropiedadImagenRepositoryInterface::class);
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $fileUploader = $this->createMock(FileUploaderInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);
        $repository
            ->expects($this->never())
            ->method('clearPrincipalByPropiedadId');

        $service = new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $fileUploader
        );

        $this->expectException(ForbiddenException::class);

        $service->establecerPrincipal(1, (object) ['sub' => 8, 'rol_id' => 1]);
    }

    public function test_elimina_una_imagen_y_registra_la_actividad(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $imagen = new PropiedadImagen(['ruta' => '/uploads/no-existe.jpg']);
        $imagen->id = 1;
        $imagen->propiedad_id = 3;
        $imagen->setRelation('propiedad', $propiedad);

        $repository = $this->createMock(PropiedadImagenRepositoryInterface::class);
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $fileUploader = $this->createMock(FileUploaderInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);
        $repository
            ->expects($this->once())
            ->method('delete')
            ->with($imagen)
            ->willReturn(true);
        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(7, 'Eliminación de imagen para propiedad ID: 3');

        (new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $fileUploader
        ))->eliminar(1, (object) ['sub' => 7, 'rol_id' => 1]);

        $this->addToAssertionCount(1);
    }

    public function test_eliminar_lanza_excepcion_si_no_tiene_permiso(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $imagen = new PropiedadImagen();
        $imagen->id = 1;
        $imagen->setRelation('propiedad', $propiedad);

        $repository = $this->createMock(PropiedadImagenRepositoryInterface::class);
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $fileUploader = $this->createMock(FileUploaderInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);
        $repository
            ->expects($this->never())
            ->method('delete');
        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $fileUploader
        );

        $this->expectException(ForbiddenException::class);

        $service->eliminar(1, (object) ['sub' => 8, 'rol_id' => 1]);
    }

    public function test_eliminar_permite_al_administrador(): void
    {
        $propiedad = new Propiedad();
        $propiedad->usuario_id = 7;

        $imagen = new PropiedadImagen(['ruta' => '/uploads/no-existe.jpg']);
        $imagen->id = 1;
        $imagen->propiedad_id = 3;
        $imagen->setRelation('propiedad', $propiedad);

        $repository = $this->createMock(PropiedadImagenRepositoryInterface::class);
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $fileUploader = $this->createMock(FileUploaderInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);
        $repository
            ->expects($this->once())
            ->method('delete')
            ->with($imagen)
            ->willReturn(true);
        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(99, 'Eliminación de imagen para propiedad ID: 3');

        (new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $fileUploader
        ))->eliminar(1, (object) ['sub' => 99, 'rol_id' => 2]);

        $this->addToAssertionCount(1);
    }
}