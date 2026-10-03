<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Categoria;
use App\Models\Localidad;
use App\Models\Propiedad;
use App\Policies\PropiedadPolicy;
use App\Repositories\CategoriaRepositoryInterface;
use App\Repositories\LocalidadRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\ReservaRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Repositories\PropiedadServicioRepositoryInterface;
use App\Repositories\ServicioRepositoryInterface;
use App\Services\LogActividadService;
use App\Services\PropiedadService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;


class PropiedadServiceTest extends TestCase
{
    private function crearServicio(
        ?PropiedadRepositoryInterface $repository = null,
        ?LogActividadService $logService = null,
        ?CategoriaRepositoryInterface $categoriaRepository = null,
        ?LocalidadRepositoryInterface $localidadRepository = null,
        ?ReservaRepositoryInterface $reservaRepository = null,
        ?UsuarioRepositoryInterface $usuarioRepository = null,
        ?ServicioRepositoryInterface $servicioRepository = null,
        ?PropiedadServicioRepositoryInterface $propiedadServicioRepository = null
    ): PropiedadService {
        return new PropiedadService(
        $repository
            ?? $this->createMock(
                PropiedadRepositoryInterface::class
            ),
        $logService
            ?? $this->createMock(
                LogActividadService::class
            ),
        $categoriaRepository
            ?? $this->createMock(
                CategoriaRepositoryInterface::class
            ),
        $localidadRepository
            ?? $this->createMock(
                LocalidadRepositoryInterface::class
            ),
        $reservaRepository
            ?? $this->createMock(
                ReservaRepositoryInterface::class
            ),
        new PropiedadPolicy(),
        $usuarioRepository
            ?? $this->createMock(
                UsuarioRepositoryInterface::class
            ),
        $servicioRepository
            ?? $this->createMock(
                ServicioRepositoryInterface::class
            ),
        $propiedadServicioRepository
            ?? $this->createMock(
                PropiedadServicioRepositoryInterface::class
            )
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
            ->willReturnCallback(
                function (string $attribute) {
                    return match ($attribute) {
                        'usuario_id' => 7,
                        'deleted_at' => '2026-01-01 00:00:00',
                        default => null,
                    };
                }
            );

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
            'servicios' => [],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Listar
    |--------------------------------------------------------------------------
    */

    public function test_listar_devuelve_propiedades_y_total(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('all')
            ->with([])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listar();

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(2, $resultado['total']);
    }

    public function test_listar_destacadas_filtra_por_destacada_y_disponible(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('all')
            ->with([
                'destacada' => 1,
                'disponible' => 1,
            ])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listarDestacadas();

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_filtra_por_categoria(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('all')
            ->with([
                'categoria_id' => [2],
            ])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listar([
            'categoria_id' => '2',
        ]);

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_filtra_por_localidad(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('all')
            ->with([
                'localidad_id' => [3],
            ])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listar([
            'localidad_id' => '3',
        ]);

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_filtra_por_categoria_y_localidad(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('all')
            ->with([
                'categoria_id' => [2],
                'localidad_id' => [3],
            ])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listar([
            'categoria_id' => '2',
            'localidad_id' => '3',
        ]);

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_filtra_por_multiples_categorias(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('all')
            ->with([
                'categoria_id' => [1, 2],
            ])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listar([
            'categoria_id' => ['1', '2'],
        ]);

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_filtra_por_multiples_localidades(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('all')
            ->with([
                'localidad_id' => [1, 2],
            ])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listar([
            'localidad_id' => ['1', '2'],
        ]);

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_filtra_por_multiples_categorias_y_localidades(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('all')
            ->with([
                'categoria_id' => [1, 2],
                'localidad_id' => [1, 2],
            ])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listar([
            'categoria_id' => ['1', '2'],
            'localidad_id' => ['1', '2'],
        ]);

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_elimina_ids_duplicados(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('all')
            ->with([
                'categoria_id' => [1, 2],
            ])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listar([
            'categoria_id' => ['1', '1', '2'],
        ]);

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_lanza_excepcion_si_el_id_de_categoria_es_invalido(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('all');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->listar([
            'categoria_id' => ['1', 'abc'],
        ]);
    }

    public function test_listar_lanza_excepcion_si_el_id_de_localidad_es_invalido(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('all');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->listar([
            'localidad_id' => ['1', 'abc'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Filtros avanzados
    |--------------------------------------------------------------------------
    */

    public function test_listar_aplica_filtros_avanzados(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('all')
            ->with([
                'precio_min' => '100000.50',
                'precio_max' => '200000',
                'cantidad_ambientes' => 3,
                'cantidad_dormitorios' => 2,
                'cantidad_banos' => 1,
                'capacidad' => 4,
            ])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listar([
            'precio_min' => '100000,50',
            'precio_max' => '200000',
            'cantidad_ambientes' => '3',
            'cantidad_dormitorios' => '2',
            'cantidad_banos' => '1',
            'capacidad' => '4',
        ]);

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_acepta_precio_minimo_igual_al_maximo(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('all')
            ->with([
                'precio_min' => '125000',
                'precio_max' => '125000',
            ])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listar([
            'precio_min' => '125000',
            'precio_max' => '125000',
        ]);

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_lanza_excepcion_si_el_precio_minimo_es_invalido(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('all');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->listar([
            'precio_min' => 'abc',
        ]);
    }

    public function test_listar_lanza_excepcion_si_el_precio_maximo_es_invalido(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('all');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->listar([
            'precio_max' => 'abc',
        ]);
    }

    public function test_listar_lanza_excepcion_si_el_precio_minimo_es_negativo(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('all');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->listar([
            'precio_min' => '-100',
        ]);
    }

    public function test_listar_lanza_excepcion_si_el_precio_maximo_es_negativo(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('all');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->listar([
            'precio_max' => '-100',
        ]);
    }

    public function test_listar_lanza_excepcion_si_el_precio_minimo_es_mayor_al_maximo(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('all');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->listar([
            'precio_min' => '200000',
            'precio_max' => '100000',
        ]);
    }

    public function test_listar_lanza_excepcion_si_la_cantidad_de_ambientes_es_invalida(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('all');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->listar([
            'cantidad_ambientes' => '2.5',
        ]);
    }

    public function test_listar_lanza_excepcion_si_la_cantidad_de_dormitorios_es_invalida(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('all');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->listar([
            'cantidad_dormitorios' => 'abc',
        ]);
    }

    public function test_listar_lanza_excepcion_si_la_cantidad_de_banos_es_invalida(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('all');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->listar([
            'cantidad_banos' => '0',
        ]);
    }

    public function test_listar_lanza_excepcion_si_la_capacidad_es_invalida(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('all');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->listar([
            'capacidad' => '-1',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Listar para admin
    |--------------------------------------------------------------------------
    */

    public function test_listar_para_admin_devuelve_propiedades_y_total(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('allParaAdmin')
            ->with([])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listarParaAdmin([]);

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_listar_para_admin_convierte_bandera_solo_eliminados(): void
    {
        $propiedades = new Collection();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('allParaAdmin')
            ->with(['solo_eliminados' => true])
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listarParaAdmin(
            ['solo_eliminados' => 'true']
        );

        $this->assertCount(0, $resultado['items']);
    }

    public function test_listar_para_admin_descarta_bandera_false(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('allParaAdmin')
            ->with([])
            ->willReturn(new Collection());

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->listarParaAdmin(
            ['solo_eliminados' => '0']
        );

        $this->assertCount(0, $resultado['items']);
    }

    /*
    |--------------------------------------------------------------------------
    | Mis propiedades / obtener
    |--------------------------------------------------------------------------
    */

    public function test_mis_propiedades_devuelve_propiedades_y_total(): void
    {
        $propiedades = new Collection([
            $this->propiedad(),
        ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('porUsuario')
            ->with(7)
            ->willReturn($propiedades);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->misPropiedades(7);

        $this->assertSame($propiedades, $resultado['items']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_obtener_devuelve_la_propiedad(): void
    {
        $propiedad = $this->propiedad();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $service = $this->crearServicio(
            repository: $repository
        );

        $resultado = $service->obtener(1);

        $this->assertSame($propiedad, $resultado);
    }

    public function test_obtener_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('findById');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->obtener('abc');
    }

    public function test_obtener_lanza_excepcion_si_la_propiedad_no_existe(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->crearServicio(
            repository: $repository
        )->obtener(999);
    }

    /*
    |--------------------------------------------------------------------------
    | Crear
    |--------------------------------------------------------------------------
    */

    public function test_crear_fuerza_destacada_cero_si_no_es_admin(): void
    {
        $datos = $this->datosPropiedad();
        $datos['destacada'] = 1;

        $categoria = new Categoria();
        $categoria->id = 2;

        $localidad = new Localidad();
        $localidad->id = 3;

        $categoriaRepository = $this->createMock(
            CategoriaRepositoryInterface::class
        );

        $categoriaRepository->method('findById')
            ->willReturn($categoria);

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $localidadRepository->method('findById')
            ->willReturn($localidad);

        $capturado = [];

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('create')
            ->willReturnCallback(
                function (array $data) use (&$capturado) {
                    $capturado = $data;

                    return $this->propiedad();
                }
            );

        $service = $this->crearServicio(
            repository: $repository,
            categoriaRepository: $categoriaRepository,
            localidadRepository: $localidadRepository
        );

        $service->crear($datos, 7, 1);

        $this->assertSame(0, $capturado['destacada']);
    }

    public function test_crear_ignora_propietario_id_si_no_es_admin(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $usuarioRepository->expects($this->never())
            ->method('findById');

        $categoriaRepository = $this->createMock(
            CategoriaRepositoryInterface::class
        );

        $categoriaRepository->method('findById')
            ->willReturn(new Categoria());

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $localidadRepository->method('findById')
            ->willReturn(new Localidad());

        $capturado = [];

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('create')
            ->willReturnCallback(
                function (array $data) use (&$capturado) {
                    $capturado = $data;

                    return $this->propiedad();
                }
            );

        $datos = $this->datosPropiedad();
        $datos['propietario_id'] = 25;

        $this->crearServicio(
            repository: $repository,
            categoriaRepository: $categoriaRepository,
            localidadRepository: $localidadRepository,
            usuarioRepository: $usuarioRepository
        )->crear(
            $datos,
            7,
            1
        );

        $this->assertSame(
            7,
            $capturado['usuario_id']
        );
    }

    public function test_crear_permite_al_admin_asignar_un_propietario(): void
    {
        $usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $usuarioRepository->expects($this->once())
            ->method('findById')
            ->with(25)
            ->willReturn(new \App\Models\Usuario());

        $categoriaRepository = $this->createMock(
            CategoriaRepositoryInterface::class
        );

        $categoriaRepository->method('findById')
            ->willReturn(new Categoria());

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $localidadRepository->method('findById')
            ->willReturn(new Localidad());

        $capturado = [];

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('create')
            ->willReturnCallback(
                function (array $data) use (&$capturado) {
                    $capturado = $data;

                    return $this->propiedad();
                }
            );

        $datos = $this->datosPropiedad();
        $datos['propietario_id'] = 25;

        $this->crearServicio(
            repository: $repository,
            categoriaRepository: $categoriaRepository,
            localidadRepository: $localidadRepository,
            usuarioRepository: $usuarioRepository
        )->crear(
            $datos,
            7,
            2
        );

        $this->assertSame(
            25,
            $capturado['usuario_id']
        );
    }

    public function test_crear_permite_destacada_si_es_admin(): void
    {
        $datos = $this->datosPropiedad();
        $datos['destacada'] = 1;

        $categoria = new Categoria();
        $categoria->id = 2;

        $localidad = new Localidad();
        $localidad->id = 3;

        $categoriaRepository = $this->createMock(
            CategoriaRepositoryInterface::class
        );

        $categoriaRepository->method('findById')
            ->willReturn($categoria);

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $localidadRepository->method('findById')
            ->willReturn($localidad);

        $capturado = [];

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('create')
            ->willReturnCallback(
                function (array $data) use (&$capturado) {
                    $capturado = $data;

                    return $this->propiedad();
                }
            );

        $service = $this->crearServicio(
            repository: $repository,
            categoriaRepository: $categoriaRepository,
            localidadRepository: $localidadRepository
        );

        $service->crear($datos, 7, 2);

        $this->assertSame(1, $capturado['destacada']);
    }

    public function test_crear_una_propiedad_y_registra_la_actividad(): void
    {
        $propiedad = $this->propiedad();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $categoriaRepository = $this->createMock(
            CategoriaRepositoryInterface::class
        );

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $categoria = new Categoria();
        $categoria->id = 2;

        $localidad = new Localidad();
        $localidad->id = 3;

        $categoriaRepository->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($categoria);

        $localidadRepository->expects($this->once())
            ->method('findById')
            ->with(3)
            ->willReturn($localidad);

        $repository->expects($this->once())
            ->method('create')
            ->with($this->callback(
                function (array $data): bool {
                    return
                        $data['titulo'] === 'Casa amplia'
                        && $data['precio'] === 125000.5
                        && $data['expensas'] === 0.0
                        && $data['categoria_id'] === 2
                        && $data['localidad_id'] === 3
                        && $data['usuario_id'] === 7;
                }
            ))
            ->willReturn($propiedad);

        $logService->expects($this->once())
            ->method('registrar')
            ->with(
                7,
                'Creación de propiedad'
            );

        $service = $this->crearServicio(
            repository: $repository,
            logService: $logService,
            categoriaRepository: $categoriaRepository,
            localidadRepository: $localidadRepository
        );

        $resultado = $service->crear(
            $this->datosPropiedad(),
            7
        );

        $this->assertSame($propiedad, $resultado);
    }

    public function test_crear_lanza_excepcion_si_los_datos_son_invalidos(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('create');

        $service = $this->crearServicio(
            repository: $repository
        );

        $this->expectException(ValidationException::class);

        $service->crear(
            [
                'titulo' => '',
                'precio' => -100,
            ],
            7
        );
    }

    public function test_crear_lanza_excepcion_si_la_categoria_no_existe(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $categoriaRepository = $this->createMock(
            CategoriaRepositoryInterface::class
        );

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $categoriaRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $localidadRepository->expects($this->never())
            ->method('findById');

        $repository->expects($this->never())
            ->method('create');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository,
            categoriaRepository: $categoriaRepository,
            localidadRepository: $localidadRepository
        )->crear(
            array_merge(
                $this->datosPropiedad(),
                [
                    'categoria_id' => 999,
                ]
            ),
            7
        );
    }

    public function test_crear_lanza_excepcion_si_la_localidad_no_existe(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $categoriaRepository = $this->createMock(
            CategoriaRepositoryInterface::class
        );

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $categoria = new Categoria();
        $categoria->id = 2;

        $categoriaRepository->expects($this->once())
            ->method('findById')
            ->with(2)
            ->willReturn($categoria);

        $localidadRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $repository->expects($this->never())
            ->method('create');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository,
            categoriaRepository: $categoriaRepository,
            localidadRepository: $localidadRepository
        )->crear(
            array_merge(
                $this->datosPropiedad(),
                [
                    'localidad_id' => 999,
                ]
            ),
            7
        );
    }

    public function test_crear_asigna_los_servicios_a_la_propiedad(): void
    {
        $propiedad = $this->propiedad();

        $categoria = new Categoria();
        $categoria->id = 2;

        $localidad = new Localidad();
        $localidad->id = 3;

        $categoriaRepository = $this->createMock(
            CategoriaRepositoryInterface::class
        );

        $categoriaRepository->method('findById')
            ->willReturn($categoria);

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $localidadRepository->method('findById')
            ->willReturn($localidad);

        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $servicioRepository->expects($this->once())
            ->method('findByIds')
            ->with([1, 2])
            ->willReturn(new Collection([
                new \App\Models\Servicio(),
                new \App\Models\Servicio(),
            ]));

        $propiedadServicioRepository = $this->createMock(
            PropiedadServicioRepositoryInterface::class
        );

        $propiedadServicioRepository->expects($this->once())
            ->method('attachMultiple')
            ->with(1, [1, 2])
            ->willReturn([
                'asignados' => [1, 2],
                'duplicados' => [],
                'errores' => [],
            ]);

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('create')
            ->willReturn($propiedad);

        $service = $this->crearServicio(
            repository: $repository,
            categoriaRepository: $categoriaRepository,
            localidadRepository: $localidadRepository,
            servicioRepository: $servicioRepository,
            propiedadServicioRepository: $propiedadServicioRepository
        );

        $datos = $this->datosPropiedad();
        $datos['servicios'] = [1, 2];

        $resultado = $service->crear(
            $datos,
            7
        );

        $this->assertSame($propiedad, $resultado);
    }

    public function test_crear_lanza_excepcion_si_un_id_de_servicio_es_invalido(): void
    {
        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $servicioRepository->expects($this->never())
            ->method('findByIds');

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('create');

        $this->expectException(ValidationException::class);

        $datos = $this->datosPropiedad();
        $datos['servicios'] = [1, 'abc'];

        $this->crearServicio(
            repository: $repository,
            servicioRepository: $servicioRepository
        )->crear(
            $datos,
            7
        );
    }

    public function test_crear_lanza_excepcion_si_un_servicio_no_existe(): void
    {
        $servicioRepository = $this->createMock(
            ServicioRepositoryInterface::class
        );

        $servicioRepository->expects($this->once())
            ->method('findByIds')
            ->with([1, 999])
            ->willReturn(new Collection([
                new \App\Models\Servicio(),
            ]));

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('create');

        $this->expectException(ValidationException::class);

        $datos = $this->datosPropiedad();
        $datos['servicios'] = [1, 999];

        $this->crearServicio(
            repository: $repository,
            servicioRepository: $servicioRepository
        )->crear(
            $datos,
            7
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function test_actualizar_ignora_destacada_si_no_es_admin(): void
    {
        $propiedad = $this->propiedad();
        $propiedad->destacada = 0;

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->method('findById')
            ->willReturn($propiedad);

        $capturado = [];

        $repository->expects($this->once())
            ->method('update')
            ->willReturnCallback(
                function (
                    Propiedad $p,
                    array $data
                ) use (&$capturado) {
                    $capturado = $data;

                    return true;
                }
            );

        $service = $this->crearServicio(
            repository: $repository
        );

        $service->actualizar(
            7,
            1,
            1,
            [
                'titulo' => 'Nuevo titulo',
                'destacada' => 1,
            ]
        );

        $this->assertArrayNotHasKey(
            'destacada',
            $capturado
        );

        $this->assertSame(
            'Nuevo titulo',
            $capturado['titulo']
        );
    }

    public function test_actualizar_permite_destacada_si_es_admin(): void
    {
        $propiedad = $this->propiedad();
        $propiedad->destacada = 0;

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->method('findById')
            ->willReturn($propiedad);

        $capturado = [];

        $repository->expects($this->once())
            ->method('update')
            ->willReturnCallback(
                function (
                    Propiedad $p,
                    array $data
                ) use (&$capturado) {
                    $capturado = $data;

                    return true;
                }
            );

        $service = $this->crearServicio(
            repository: $repository
        );

        $service->actualizar(
            7,
            2,
            1,
            [
                'destacada' => 1,
            ]
        );

        $this->assertSame(
            1,
            $capturado['destacada']
        );
    }

    public function test_actualizar_una_propiedad_y_registra_la_actividad(): void
    {
        $propiedad = $this->propiedad();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $repository->expects($this->once())
            ->method('update')
            ->with(
                $propiedad,
                $this->callback(
                    function (array $data): bool {
                        return
                            $data['titulo'] === 'Casa modificada'
                            && $data['precio'] === 150000.5
                            && count($data) === 2;
                    }
                )
            )
            ->willReturn(true);

        $logService->expects($this->once())
            ->method('registrar')
            ->with(
                7,
                'Actualización de propiedad'
            );

        $this->crearServicio(
            repository: $repository,
            logService: $logService
        )->actualizar(
            7,
            1,
            1,
            [
                'titulo' => 'Casa modificada',
                'precio' => '150000,50',
            ]
        );
    }

    public function test_actualizar_una_propiedad_si_es_administrador(): void
    {
        $propiedad = $this->propiedad();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $repository->expects($this->once())
            ->method('update')
            ->with(
                $propiedad,
                [
                    'precio' => 200000.0,
                ]
            )
            ->willReturn(true);

        $logService->expects($this->once())
            ->method('registrar')
            ->with(
                99,
                'Actualización de propiedad'
            );

        $this->crearServicio(
            repository: $repository,
            logService: $logService
        )->actualizar(
            99,
            2,
            1,
            [
                'precio' => 200000,
            ]
        );
    }

    public function test_actualizar_lanza_excepcion_si_no_tiene_permiso(): void
    {
        $propiedad = $this->propiedad();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $repository->expects($this->never())
            ->method('update');

        $this->expectException(ForbiddenException::class);

        $this->crearServicio(
            repository: $repository
        )->actualizar(
            99,
            1,
            1,
            [
                'titulo' => 'No permitido',
            ]
        );
    }

    public function test_actualizar_lanza_excepcion_si_no_se_envian_campos(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('findById');

        $this->expectException(BadRequestException::class);

        $this->crearServicio(
            repository: $repository
        )->actualizar(
            7,
            1,
            1,
            []
        );
    }

    public function test_actualizar_lanza_excepcion_si_no_hay_campos_actualizables(): void
    {
        $propiedad = $this->propiedad();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $repository->expects($this->never())
            ->method('update');

        $this->expectException(BadRequestException::class);

        $this->crearServicio(
            repository: $repository
        )->actualizar(
            7,
            1,
            1,
            [
                'id' => 999,
                'usuario_id' => 999,
                'deleted_at' => '2026-01-01',
            ]
        );
    }

    public function test_actualizar_lanza_excepcion_si_el_estado_final_es_invalido(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($this->propiedad());

        $repository->expects($this->never())
            ->method('update');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->actualizar(
            7,
            1,
            1,
            [
                'cantidad_ambientes' => 1,
            ]
        );
    }

    public function test_actualizar_lanza_excepcion_si_la_categoria_no_existe(): void
    {
        $propiedad = $this->propiedad();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $categoriaRepository = $this->createMock(
            CategoriaRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $categoriaRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $repository->expects($this->never())
            ->method('update');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository,
            categoriaRepository: $categoriaRepository
        )->actualizar(
            7,
            1,
            1,
            [
                'categoria_id' => 999,
            ]
        );
    }

    public function test_actualizar_lanza_excepcion_si_la_localidad_no_existe(): void
    {
        $propiedad = $this->propiedad();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $localidadRepository = $this->createMock(
            LocalidadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $localidadRepository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $repository->expects($this->never())
            ->method('update');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository,
            localidadRepository: $localidadRepository
        )->actualizar(
            7,
            1,
            1,
            [
                'localidad_id' => 999,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function test_eliminar_una_propiedad_y_registra_la_actividad(): void
    {
        $propiedad = $this->propiedad();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $reservaRepository = $this->createMock(
            ReservaRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $reservaRepository->expects($this->once())
            ->method('tieneReservaActiva')
            ->with(1)
            ->willReturn(false);

        $repository->expects($this->once())
            ->method('delete')
            ->with($propiedad)
            ->willReturn(true);

        $logService->expects($this->once())
            ->method('registrar')
            ->with(
                7,
                'Eliminación de propiedad'
            );

        $this->crearServicio(
            repository: $repository,
            logService: $logService,
            reservaRepository: $reservaRepository
        )->eliminar(
            7,
            1,
            1
        );
    }

    public function test_eliminar_lanza_excepcion_si_no_tiene_permiso(): void
    {
        $propiedad = $this->propiedad();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $reservaRepository = $this->createMock(
            ReservaRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $reservaRepository->expects($this->never())
            ->method('tieneReservaActiva');

        $repository->expects($this->never())
            ->method('delete');

        $this->expectException(ForbiddenException::class);

        $this->crearServicio(
            repository: $repository,
            reservaRepository: $reservaRepository
        )->eliminar(
            99,
            1,
            1
        );
    }

    public function test_eliminar_lanza_excepcion_si_tiene_reserva_activa(): void
    {
        $propiedad = $this->propiedad();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $reservaRepository = $this->createMock(
            ReservaRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($propiedad);

        $reservaRepository->expects($this->once())
            ->method('tieneReservaActiva')
            ->with(1)
            ->willReturn(true);

        $repository->expects($this->never())
            ->method('delete');

        $logService->expects($this->never())
            ->method('registrar');

        $this->expectException(ConflictException::class);

        $this->expectExceptionMessage(
            'No se puede eliminar la propiedad porque tiene una reserva activa'
        );

        $this->crearServicio(
            repository: $repository,
            logService: $logService,
            reservaRepository: $reservaRepository
        )->eliminar(
            7,
            1,
            1
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Restaurar
    |--------------------------------------------------------------------------
    */

    public function test_restaurar_una_propiedad_y_registra_la_actividad(): void
    {
        $propiedad = $this->propiedadEliminada();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $logService = $this->createMock(
            LogActividadService::class
        );

        $repository->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($propiedad);

        $repository->expects($this->once())
            ->method('restore')
            ->with($propiedad)
            ->willReturn(true);

        $logService->expects($this->once())
            ->method('registrar')
            ->with(
                7,
                'Restauración de propiedad'
            );

        $this->crearServicio(
            repository: $repository,
            logService: $logService
        )->restaurar(
            7,
            1,
            1
        );
    }

    public function test_restaurar_lanza_excepcion_si_no_existe(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('findDeletedById')
            ->with(999)
            ->willReturn(null);

        $repository->expects($this->never())
            ->method('restore');

        $this->expectException(NotFoundException::class);

        $this->crearServicio(
            repository: $repository
        )->restaurar(
            7,
            1,
            999
        );
    }

    public function test_restaurar_lanza_excepcion_si_el_id_es_invalido(): void
    {
        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->never())
            ->method('findDeletedById');

        $this->expectException(ValidationException::class);

        $this->crearServicio(
            repository: $repository
        )->restaurar(
            7,
            1,
            'abc'
        );
    }

    public function test_restaurar_lanza_excepcion_si_no_tiene_permiso(): void
    {
        $propiedad = $this->propiedadEliminada();

        $repository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $repository->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($propiedad);

        $repository->expects($this->never())
            ->method('restore');

        $this->expectException(ForbiddenException::class);

        $this->crearServicio(
            repository: $repository
        )->restaurar(
            99,
            1,
            1
        );
    }
}