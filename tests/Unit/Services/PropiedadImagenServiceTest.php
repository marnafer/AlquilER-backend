<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Propiedad;
use App\Models\PropiedadImagen;
use App\Policies\PropiedadImagenPolicy;
use App\Repositories\PropiedadImagenRepositoryInterface;
use App\Services\GestorArchivosInterface;
use App\Services\LogActividadService;
use App\Services\PropiedadImagenService;
use App\Services\PropiedadService;
use App\Validators\CargaImagenValidatorInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

final class PropiedadImagenServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
    
    public function test_lista_todas_las_imagenes_y_devuelve_el_total(): void
    {
        $imagenes = new Collection([
            new PropiedadImagen(['ruta' => '/uploads/uno.jpg']),
            new PropiedadImagen(['ruta' => '/uploads/dos.jpg']),
        ]);

        $repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $gestorArchivos = $this->createMock(
            GestorArchivosInterface::class
        );
        $cargaImagenValidator = $this->createMock(
            CargaImagenValidatorInterface::class
        );
        $policy = $this->createMock(PropiedadImagenPolicy::class);

        $repository
            ->expects($this->once())
            ->method('all')
            ->willReturn($imagenes);

        $resultado = (new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $gestorArchivos,
            $cargaImagenValidator,
            $policy
        ))->listar();

        $this->assertSame($imagenes, $resultado['items']);
        $this->assertSame(2, $resultado['total']);
    }

    public function test_obtener_devuelve_una_imagen_existente(): void
    {
        $imagen = new PropiedadImagen([
            'ruta' => '/uploads/uno.jpg'
        ]);
        $imagen->id = 1;

        $repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $gestorArchivos = $this->createMock(
            GestorArchivosInterface::class
        );
        $cargaImagenValidator = $this->createMock(
            CargaImagenValidatorInterface::class
        );
        $policy = $this->createMock(PropiedadImagenPolicy::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);

        $resultado = (new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $gestorArchivos,
            $cargaImagenValidator,
            $policy
        ))->obtener(1);

        $this->assertSame($imagen, $resultado);
    }

    public function test_obtener_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $gestorArchivos = $this->createMock(
            GestorArchivosInterface::class
        );
        $cargaImagenValidator = $this->createMock(
            CargaImagenValidatorInterface::class
        );
        $policy = $this->createMock(PropiedadImagenPolicy::class);

        $repository
            ->expects($this->never())
            ->method('findById');

        $service = new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $gestorArchivos,
            $cargaImagenValidator,
            $policy
        );

        $this->expectException(ValidationException::class);

        $service->obtener('abc');
    }

    public function test_obtener_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $gestorArchivos = $this->createMock(
            GestorArchivosInterface::class
        );
        $cargaImagenValidator = $this->createMock(
            CargaImagenValidatorInterface::class
        );
        $policy = $this->createMock(PropiedadImagenPolicy::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null);

        $service = new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $gestorArchivos,
            $cargaImagenValidator,
            $policy
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Imagen no encontrada');

        $service->obtener(1);
    }

    public function test_crear_lanza_excepcion_si_no_se_envia_una_imagen_valida(): void
    {
        $repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $gestorArchivos = $this->createMock(
            GestorArchivosInterface::class
        );
        $cargaImagenValidator = $this->createMock(
            CargaImagenValidatorInterface::class
        );
        $policy = $this->createMock(PropiedadImagenPolicy::class);

        $propiedadService
            ->expects($this->never())
            ->method('obtener');

        $repository
            ->expects($this->never())
            ->method('create');

        $cargaImagenValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn('Debe enviar una imagen');

        $service = new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $gestorArchivos,
            $cargaImagenValidator,
            $policy
        );

        $this->expectException(ValidationException::class);

        $service->crear(
            ['propiedad_id' => 1],
            [],
            1,
            2
        );
    }

    public function test_crear_sin_permiso(): void
    {
        $repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $gestorArchivos = $this->createMock(
            GestorArchivosInterface::class
        );
        $cargaImagenValidator = $this->createMock(
            CargaImagenValidatorInterface::class
        );
        $policy = $this->createMock(PropiedadImagenPolicy::class);

        $rawData = [
            'propiedad_id' => 1,
            'descripcion' => 'Imagen de prueba',
        ];

        $file = [
            'name' => 'imagen.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/imagen.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => 1000,
        ];

        $usuarioId = 7;
        $rolId = 1;

        $propiedad = new Propiedad([
            'id' => 1,
            'usuario_id' => 99,
        ]);

        $cargaImagenValidator
            ->expects($this->once())
            ->method('validate')
            ->with($file)
            ->willReturn(null);

        $propiedadService
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($propiedad);

        $policy
            ->expects($this->once())
            ->method('gestionarPropiedad')
            ->with($usuarioId, $rolId, $propiedad)
            ->willThrowException(
                new ForbiddenException(
                    'No tiene permisos sobre esta propiedad'
                )
            );

        $gestorArchivos
            ->expects($this->never())
            ->method('upload');

        $repository
            ->expects($this->never())
            ->method('countByPropiedadId');

        $repository
            ->expects($this->never())
            ->method('create');

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $gestorArchivos,
            $cargaImagenValidator,
            $policy
        );

        $this->expectException(ForbiddenException::class);

        $service->crear(
            $rawData,
            $file,
            $usuarioId,
            $rolId
        );
    }

    public function test_creacion_exitosa(): void
    {
        $propiedad = new Propiedad();
        $propiedad->id = 1;
        $propiedad->usuario_id = 7;

        $usuarioId = 7;
        $rolId = 1;

        $imagen = new PropiedadImagen([
            'ruta' => '/uploads/imagen.jpg',
        ]);

        $repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $gestorArchivos = $this->createMock(
            GestorArchivosInterface::class
        );
        $cargaImagenValidator = $this->createMock(
            CargaImagenValidatorInterface::class
        );
        $policy = $this->createMock(PropiedadImagenPolicy::class);

        $propiedadService
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($propiedad);

        $cargaImagenValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(null);

        $policy
            ->expects($this->once())
            ->method('gestionarPropiedad')
            ->with($usuarioId, $rolId, $propiedad);

        $gestorArchivos
            ->expects($this->once())
            ->method('upload')
            ->willReturn('imagen.jpg');

        $repository
            ->expects($this->once())
            ->method('countByPropiedadId')
            ->with(1)
            ->willReturn(0);

        $repository
            ->expects($this->once())
            ->method('create')
            ->with([
                'propiedad_id' => 1,
                'ruta' => '/uploads/propiedades/imagen.jpg',
                'descripcion' => null,
                'es_principal' => 1,
            ])
            ->willReturn($imagen);

        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                $usuarioId,
                'Creación de imagen para propiedad ID: 1'
            );

        $resultado = (new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $gestorArchivos,
            $cargaImagenValidator,
            $policy
        ))->crear(
            ['propiedad_id' => 1],
            ['tmp_name' => '/tmp/php123', 'size' => 1024],
            $usuarioId,
            $rolId
        );

        $this->assertSame($imagen, $resultado);
    }

    public function test_establece_una_imagen_como_principal(): void
    {
        $imagen = $this->getMockBuilder(PropiedadImagen::class)
            ->onlyMethods(['refresh'])
            ->getMock();

        $imagen->id = 1;
        $imagen->propiedad_id = 3;

        $usuarioId = 7;
        $rolId = 1;

        $repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $gestorArchivos = $this->createMock(
            GestorArchivosInterface::class
        );
        $cargaImagenValidator = $this->createMock(
            CargaImagenValidatorInterface::class
        );
        $policy = $this->createMock(PropiedadImagenPolicy::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($usuarioId, $rolId, $imagen);

        $repository
            ->expects($this->once())
            ->method('clearPrincipalByPropiedadId')
            ->with(3);

        $repository
            ->expects($this->once())
            ->method('setPrincipal')
            ->with($imagen);

        $imagen
            ->expects($this->once())
            ->method('refresh')
            ->willReturn($imagen);

        $resultado = (new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $gestorArchivos,
            $cargaImagenValidator,
            $policy
        ))->establecerPrincipal(
            1,
            $usuarioId,
            $rolId
        );

        $this->assertSame($imagen, $resultado);
    }

    public function test_establecer_principal_lanza_excepcion_sin_permiso(): void
    {
        $imagen = new PropiedadImagen();
        $imagen->id = 1;
        $imagen->propiedad_id = 3;

        $repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $gestorArchivos = $this->createMock(
            GestorArchivosInterface::class
        );
        $cargaImagenValidator = $this->createMock(
            CargaImagenValidatorInterface::class
        );
        $policy = $this->createMock(PropiedadImagenPolicy::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with(8, 1, $imagen)
            ->willThrowException(
                new ForbiddenException(
                    'No tiene permisos sobre esta imagen'
                )
            );

        $repository
            ->expects($this->never())
            ->method('clearPrincipalByPropiedadId');

        $repository
            ->expects($this->never())
            ->method('setPrincipal');

        $service = new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $gestorArchivos,
            $cargaImagenValidator,
            $policy
        );

        $this->expectException(ForbiddenException::class);

        $service->establecerPrincipal(1, 8, 1);
    }

    public function test_elimina_una_imagen_y_registra_la_actividad(): void
    {
        $imagen = new PropiedadImagen([
            'ruta' => '/uploads/no-existe.jpg',
            'es_principal' => 0,
        ]);

        $imagen->id = 1;
        $imagen->propiedad_id = 3;

        $usuarioId = 7;
        $rolId = 1;

        $repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $gestorArchivos = $this->createMock(
            GestorArchivosInterface::class
        );
        $cargaImagenValidator = $this->createMock(
            CargaImagenValidatorInterface::class
        );
        $policy = $this->createMock(PropiedadImagenPolicy::class
        );

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with($usuarioId, $rolId, $imagen);

        $repository
            ->expects($this->once())
            ->method('delete')
            ->with($imagen);

        $gestorArchivos
            ->expects($this->once())
            ->method('delete')
            ->with('/uploads/no-existe.jpg');

        $logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                $usuarioId,
                'Eliminación de imagen para propiedad ID: 3'
            );

        (new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $gestorArchivos,
            $cargaImagenValidator,
            $policy
        ))->eliminar(
            1,
            $usuarioId,
            $rolId
        );

        $this->addToAssertionCount(1);
    }

    public function test_eliminar_lanza_excepcion_si_no_tiene_permiso(): void
    {
        $imagen = new PropiedadImagen();
        $imagen->id = 1;
        $imagen->propiedad_id = 3;

        $repository = $this->createMock(
            PropiedadImagenRepositoryInterface::class
        );
        $propiedadService = $this->createMock(PropiedadService::class);
        $logService = $this->createMock(LogActividadService::class);
        $gestorArchivos = $this->createMock(
            GestorArchivosInterface::class
        );
        $cargaImagenValidator = $this->createMock(
            CargaImagenValidatorInterface::class
        );
        $policy = $this->createMock(PropiedadImagenPolicy::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($imagen);

        $policy
            ->expects($this->once())
            ->method('gestionar')
            ->with(8, 1, $imagen)
            ->willThrowException(
                new ForbiddenException(
                    'No tiene permisos sobre esta imagen'
                )
            );

        $repository
            ->expects($this->never())
            ->method('delete');

        $gestorArchivos
            ->expects($this->never())
            ->method('delete');

        $logService
            ->expects($this->never())
            ->method('registrar');

        $service = new PropiedadImagenService(
            $repository,
            $propiedadService,
            $logService,
            $gestorArchivos,
            $cargaImagenValidator,
            $policy
        );

        $this->expectException(ForbiddenException::class);

        $service->eliminar(1, 8, 1);
    }
}