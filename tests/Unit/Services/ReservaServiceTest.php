<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Propiedad;
use App\Models\Reserva;
use App\Models\Rol;
use App\Policies\ReservaPolicy;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\ReservaRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\LogActividadService;
use App\Services\NotificacionService;
use App\Services\ReservaService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

final class ReservaServiceTest extends TestCase
{
    /**
     * Fecha de inicio utilizable para crear reservas. El validador rechaza
     * fechas en el pasado, asi que se calcula relativa a hoy en vez de fija,
     * para que estos casos no se rompan al correr el suite otro dia.
     */
    private const FECHA_INICIO = '+30 days';

    private $reservaRepository;
    private $propiedadRepository;
    private $reservaPolicy;
    private $logService;
    private $notificacionService;
    private $usuarioRepository;
    private $reservaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reservaRepository = $this->createMock(
            ReservaRepositoryInterface::class
        );

        $this->propiedadRepository = $this->createMock(
            PropiedadRepositoryInterface::class
        );

        $this->reservaPolicy = $this->createMock(
            ReservaPolicy::class
        );

        $this->logService = $this->createMock(
            LogActividadService::class
        );

        $this->notificacionService = $this->createMock(
            NotificacionService::class
        );

        $this->usuarioRepository = $this->createMock(
            UsuarioRepositoryInterface::class
        );

        $this->reservaService = new ReservaService(
            $this->reservaRepository,
            $this->propiedadRepository,
            $this->reservaPolicy,
            $this->logService,
            $this->notificacionService,
            $this->usuarioRepository
        );
    }

    public function test_it_can_list_reservas_as_admin(): void
    {
        $filtros = [
            'estado' => 'pendiente',
        ];

        $reservas = [
            [
                'id' => 1,
                'estado' => 'pendiente',
            ],
        ];

        $this->reservaRepository
            ->expects($this->once())
            ->method('getAll')
            ->with($filtros)
            ->willReturn($reservas);

        $this->propiedadRepository
            ->expects($this->never())
            ->method('porUsuario');

        $this->reservaRepository
            ->expects($this->never())
            ->method('listarPorAlcance');

        $result = $this->reservaService->listar(
            99,
            Rol::ADMIN,
            $filtros
        );

        $this->assertSame(
            [
                'mis_reservas' => [],
                'reservas_de_mis_propiedades' => [],
                'todas' => $reservas,
            ],
            $result
        );
    }

    public function test_it_lists_reservas_within_user_scope(): void
    {
        $reservas = [
            [
                'id' => 1,
                'usuario_id' => 1,
                'propiedad_id' => 10,
                'fecha_reserva' => '2026-09-15 10:00:00',
            ],
        ];

        $propiedad = new Propiedad([
            'usuario_id' => 1,
        ]);

        $propiedad->setAttribute('id', 20);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('porUsuario')
            ->with(1)
            ->willReturn(
                new Collection([$propiedad])
            );

        $this->reservaRepository
            ->expects($this->once())
            ->method('listarPorAlcance')
            ->with(1, [20], [])
            ->willReturn($reservas);

        $this->reservaRepository
            ->expects($this->never())
            ->method('getAll');

        $result = $this->reservaService->listar(
            1,
            1
        );

        $this->assertSame(
            [
                'mis_reservas' => $reservas,
                'reservas_de_mis_propiedades' => [],
            ],
            $result
        );
    }

    public function test_it_lists_only_user_reservas_when_user_has_no_properties(): void
    {
        $reservas = [
            [
                'id' => 1,
                'usuario_id' => 1,
                'propiedad_id' => 10,
            ],
        ];

        $this->propiedadRepository
            ->expects($this->once())
            ->method('porUsuario')
            ->with(1)
            ->willReturn(
                new Collection()
            );

        $this->reservaRepository
            ->expects($this->once())
            ->method('listarPorAlcance')
            ->with(1, [], [])
            ->willReturn($reservas);

        $this->reservaRepository
            ->expects($this->never())
            ->method('getAll');

        $result = $this->reservaService->listar(
            1,
            1
        );

        $this->assertSame(
            [
                'mis_reservas' => $reservas,
                'reservas_de_mis_propiedades' => [],
            ],
            $result
        );
    }

    public function test_it_separates_reservas_i_made_from_those_on_my_properties(): void
    {
        $propias = [
            [
                'id' => 1,
                'usuario_id' => 1,
                'propiedad_id' => 10,
            ],
        ];

        $recibidas = [
            [
                'id' => 2,
                'usuario_id' => 7,
                'propiedad_id' => 20,
            ],
        ];

        $propiedad = new Propiedad([
            'usuario_id' => 1,
        ]);

        $propiedad->setAttribute('id', 20);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('porUsuario')
            ->with(1)
            ->willReturn(
                new Collection([$propiedad])
            );

        $this->reservaRepository
            ->expects($this->once())
            ->method('listarPorAlcance')
            ->with(1, [20], [])
            ->willReturn(array_merge($propias, $recibidas));

        $result = $this->reservaService->listar(
            1,
            1
        );

        $this->assertSame(
            [
                'mis_reservas' => $propias,
                'reservas_de_mis_propiedades' => $recibidas,
            ],
            $result
        );
    }

    public function test_it_applies_filters_when_listing_reservas_within_scope(): void
    {
        $filtros = [
            'estado' => 'pendiente',
            'propiedad_id' => 20,
        ];

        $propiedad = new Propiedad([
            'usuario_id' => 1,
        ]);

        $propiedad->setAttribute('id', 20);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('porUsuario')
            ->with(1)
            ->willReturn(
                new Collection([$propiedad])
            );

        $this->reservaRepository
            ->expects($this->once())
            ->method('listarPorAlcance')
            ->with(1, [20], $filtros)
            ->willReturn([]);

        $this->reservaRepository
            ->expects($this->never())
            ->method('getAll');

        $result = $this->reservaService->listar(
            1,
            1,
            $filtros
        );

        $this->assertSame(
            [
                'mis_reservas' => [],
                'reservas_de_mis_propiedades' => [],
            ],
            $result
        );
    }

    public function test_it_can_get_reserva_by_id(): void
    {
        $reserva = new Reserva([
            'id' => 1,
            'estado' => 'pendiente',
            'usuario_id' => 1,
            'propiedad_id' => 10,
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reserva);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeVer')
            ->with(1, 1, $reserva)
            ->willReturn(true);

        $result = $this->reservaService->obtener(
            1,
            1,
            1
        );

        $this->assertSame($reserva, $result);
    }

    public function test_it_throws_exception_when_reserva_not_found(): void
    {
        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->reservaPolicy
            ->expects($this->never())
            ->method('puedeVer');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Reserva no encontrada'
        );

        $this->reservaService->obtener(
            999,
            1,
            1
        );
    }

    public function test_it_throws_exception_when_user_cannot_view_reserva(): void
    {
        $reserva = new Reserva([
            'id' => 1,
            'usuario_id' => 5,
            'propiedad_id' => 10,
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reserva);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeVer')
            ->with(1, 1, $reserva)
            ->willReturn(false);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tienes permiso para consultar esta reserva'
        );

        $this->reservaService->obtener(
            1,
            1,
            1
        );
    }

    public function test_it_throws_validation_exception_when_obtener_id_is_invalid(): void
    {
        $this->reservaRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(ValidationException::class);

        $this->reservaService->obtener(
            'abc',
            1,
            1
        );
    }

    public function test_it_can_create_reserva(): void
    {
        $propiedad = new Propiedad([
            'id' => 10,
            'usuario_id' => 5,
            'disponible' => true,
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($propiedad);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeCrear')
            ->with(1, 5)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                function (array $data): bool {
                    return
                        $data['propiedad_id'] === 10 &&
                        $data['usuario_id'] === 1 &&
                        $data['estado'] === 'pendiente';
                }
            ))
            ->willReturn(20);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                1,
                'reserva_creada'
            );

        $this->notificacionService
            ->expects($this->once())
            ->method('crear')
            ->with(
                5,
                'reserva_nueva',
                'Nueva solicitud de reserva',
                $this->isType('string'),
                20
            );

        $result = $this->reservaService->crear(
            [
                'propiedad_id' => 10,
                'fecha_inicio_alquiler' => date('Y-m-d', strtotime(self::FECHA_INICIO)),
            ],
            1
        );

        $this->assertSame(20, $result);
    }

    public function test_it_rejects_creating_reserva_overlapping_a_confirmed_one(): void
    {
        $propiedad = new Propiedad([
            'id' => 10,
            'usuario_id' => 5,
            'disponible' => true,
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($propiedad);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeCrear')
            ->with(1, 5)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('hayReservaConfirmadaSolapada')
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->never())
            ->method('create');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage(
            'La propiedad ya tiene una reserva confirmada en esas fechas'
        );

        $this->reservaService->crear(
            [
                'propiedad_id' => 10,
                'fecha_inicio_alquiler' => date('Y-m-d', strtotime(self::FECHA_INICIO)),
            ],
            1
        );
    }

    public function test_it_rejects_confirming_reserva_overlapping_a_confirmed_one(): void
    {
        $reserva = new Reserva([
            'id' => 1,
            'estado' => 'pendiente',
            'usuario_id' => 1,
            'propiedad_id' => 10,
        ]);

        $reserva->setAttribute('id', 1);
        $reserva->setAttribute(
            'fecha_inicio_alquiler',
            date('Y-m-d', strtotime(self::FECHA_INICIO))
        );

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reserva);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeVer')
            ->willReturn(true);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeConfirmar')
            ->willReturn(true);

        // La reserva se excluye a si misma del chequeo de solapamiento.
        $this->reservaRepository
            ->expects($this->once())
            ->method('hayReservaConfirmadaSolapada')
            ->with(
                10,
                date('Y-m-d', strtotime(self::FECHA_INICIO)),
                null,
                1
            )
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->never())
            ->method('update');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage(
            'La propiedad ya tiene una reserva confirmada en esas fechas'
        );

        $this->reservaService->confirmar(
            1,
            5,
            1
        );
    }

    public function test_it_rejects_reserva_whose_end_is_not_after_start(): void
    {
        $this->expectException(ValidationException::class);

        $this->reservaService->crear(
            [
                'propiedad_id' => 10,
                'fecha_inicio_alquiler' => date('Y-m-d', strtotime(self::FECHA_INICIO)),
                'fecha_fin_alquiler' => date('Y-m-d', strtotime(self::FECHA_INICIO)),
            ],
            1
        );
    }

    public function test_it_rejects_reserva_starting_in_the_past(): void
    {
        $this->expectException(ValidationException::class);

        $this->reservaService->crear(
            [
                'propiedad_id' => 10,
                'fecha_inicio_alquiler' => date('Y-m-d', strtotime('-10 days')),
            ],
            1
        );
    }

    public function test_it_throws_exception_when_creating_reserva_with_non_existent_property(): void
    {
        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->reservaPolicy
            ->expects($this->never())
            ->method('puedeCrear');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'La propiedad no existe'
        );

        $this->reservaService->crear(
            [
                'propiedad_id' => 999,
                'fecha_inicio_alquiler' => date('Y-m-d', strtotime(self::FECHA_INICIO)),
            ],
            1
        );
    }

    public function test_it_throws_exception_when_creating_reserva_for_own_property(): void
    {
        $propiedad = new Propiedad([
            'id' => 10,
            'usuario_id' => 1,
            'disponible' => true,
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($propiedad);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeCrear')
            ->with(1, 1)
            ->willReturn(false);

        $this->reservaRepository
            ->expects($this->never())
            ->method('create');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No puedes reservar una propiedad propia'
        );

        $this->reservaService->crear(
            [
                'propiedad_id' => 10,
                'fecha_inicio_alquiler' => date('Y-m-d', strtotime(self::FECHA_INICIO)),
            ],
            1
        );
    }

    public function test_it_throws_exception_when_property_is_not_available(): void
    {
        $propiedad = new Propiedad([
            'id' => 10,
            'usuario_id' => 5,
            'disponible' => false,
        ]);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($propiedad);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeCrear')
            ->with(1, 5)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->never())
            ->method('create');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage(
            'La propiedad no está disponible para alquiler'
        );

        $this->reservaService->crear(
            [
                'propiedad_id' => 10,
                'fecha_inicio_alquiler' => date('Y-m-d', strtotime(self::FECHA_INICIO)),
            ],
            1
        );
    }

    public function test_it_throws_validation_exception_when_create_data_is_invalid(): void
    {
        $this->propiedadRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(ValidationException::class);

        $this->reservaService->crear(
            [],
            1
        );
    }

    public function test_it_can_confirm_reserva(): void
    {
        $reserva = new Reserva([
            'id' => 1,
            'estado' => 'pendiente',
            'usuario_id' => 1,
            'propiedad_id' => 10,
        ]);

        $reserva->setAttribute('id', 1);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reserva);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeVer')
            ->with(5, 1, $reserva)
            ->willReturn(true);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeConfirmar')
            ->with(5, 1, $reserva)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                1,
                $this->callback(
                    function (array $data): bool {
                        return
                            $data['estado'] === 'confirmada' &&
                            isset($data['fecha_confirmacion']);
                    }
                )
            )
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                5,
                'reserva_confirmada'
            );

        $this->notificacionService
            ->expects($this->once())
            ->method('crear')
            ->with(
                1,
                'reserva_confirmada',
                'Reserva confirmada',
                $this->isType('string'),
                1
            );

        $result = $this->reservaService->confirmar(
            1,
            5,
            1
        );

        $this->assertTrue($result);
    }

    public function test_it_can_confirm_reserva_as_admin(): void
    {
        $reserva = new Reserva([
            'id' => 1,
            'estado' => 'pendiente',
            'usuario_id' => 1,
            'propiedad_id' => 10,
        ]);

        $reserva->setAttribute('id', 1);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reserva);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeVer')
            ->with(99, 2, $reserva)
            ->willReturn(true);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeConfirmar')
            ->with(99, 2, $reserva)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                1,
                $this->callback(
                    function (array $data): bool {
                        return
                            $data['estado'] === 'confirmada' &&
                            isset($data['fecha_confirmacion']);
                    }
                )
            )
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                99,
                'reserva_confirmada'
            );

        $this->notificacionService
            ->expects($this->once())
            ->method('crear')
            ->with(
                1,
                'reserva_confirmada',
                'Reserva confirmada',
                $this->isType('string'),
                1
            );

        $result = $this->reservaService->confirmar(
            1,
            99,
            Rol::ADMIN
        );

        $this->assertTrue($result);
    }

    public function test_it_throws_exception_when_confirming_non_pending_reserva(): void
    {
        $reserva = new Reserva([
            'id' => 1,
            'estado' => 'rechazada',
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reserva);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeVer')
            ->willReturn(true);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeConfirmar')
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->never())
            ->method('update');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'Solo se puede confirmar una reserva pendiente'
        );

        $this->reservaService->confirmar(
            1,
            5,
            1
        );
    }

    public function test_it_can_reject_reserva(): void
    {
        $reserva = new Reserva([
            'id' => 1,
            'estado' => 'pendiente',
            'usuario_id' => 8,
        ]);

        $reserva->setAttribute('id', 1);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reserva);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeVer')
            ->willReturn(true);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeRechazar')
            ->with(5, 1, $reserva)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                1,
                [
                    'estado' => 'rechazada',
                    'fecha_confirmacion' => null,
                ]
            )
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                5,
                'reserva_rechazada'
            );

        $this->notificacionService
            ->expects($this->once())
            ->method('crear')
            ->with(
                8,
                'reserva_rechazada',
                'Reserva rechazada',
                $this->isType('string'),
                1
            );

        $result = $this->reservaService->rechazar(
            1,
            5,
            1
        );

        $this->assertTrue($result);
    }

    public function test_it_throws_exception_when_rejecting_non_pending_reserva(): void
    {
        $reserva = new Reserva([
            'id' => 1,
            'estado' => 'confirmada',
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reserva);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeVer')
            ->willReturn(true);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeRechazar')
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->never())
            ->method('update');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'Solo se puede rechazar una reserva pendiente'
        );

        $this->reservaService->rechazar(
            1,
            5,
            1
        );
    }

    public function test_it_can_update_reserva_as_admin(): void
    {
        $reserva = new Reserva([
            'id' => 1,
            'estado' => 'pendiente',
        ]);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeModificar')
            ->with(Rol::ADMIN)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reserva);

        $this->reservaRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                1,
                $this->callback(
                    function (array $data): bool {
                        return
                            $data['estado'] === 'confirmada' &&
                            isset($data['fecha_confirmacion']);
                    }
                )
            )
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                99,
                'reserva_actualizada'
            );

        $result = $this->reservaService->actualizar(
            1,
            [
                'estado' => 'confirmada',
            ],
            99,
            Rol::ADMIN
        );

        $this->assertTrue($result);
    }

    public function test_it_can_update_reserva_to_rechazada_as_admin(): void
    {
        $reserva = new Reserva([
            'id' => 1,
            'estado' => 'pendiente',
        ]);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeModificar')
            ->with(Rol::ADMIN)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reserva);

        $this->reservaRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                1,
                [
                    'estado' => 'rechazada',
                    'fecha_confirmacion' => null,
                ]
            )
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                99,
                'reserva_actualizada'
            );

        $result = $this->reservaService->actualizar(
            1,
            [
                'estado' => 'rechazada',
            ],
            99,
            Rol::ADMIN
        );

        $this->assertTrue($result);
    }

    public function test_it_throws_exception_when_non_admin_updates_reserva(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeModificar')
            ->with(1)
            ->willReturn(false);

        $this->reservaRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'Solo un administrador puede modificar reservas'
        );

        $this->reservaService->actualizar(
            1,
            [
                'estado' => 'confirmada',
            ],
            1,
            1
        );
    }

    public function test_it_throws_validation_exception_when_update_estado_is_invalid(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeModificar')
            ->with(Rol::ADMIN)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(ValidationException::class);

        $this->reservaService->actualizar(
            1,
            [
                'estado' => 'invalido',
            ],
            99,
            Rol::ADMIN
        );
    }

    public function test_it_throws_exception_when_updating_non_existent_reserva(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeModificar')
            ->with(Rol::ADMIN)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Reserva no encontrada'
        );

        $this->reservaService->actualizar(
            999,
            [
                'estado' => 'confirmada',
            ],
            99,
            Rol::ADMIN
        );
    }

    public function test_it_can_delete_reserva_as_admin(): void
    {
        $reserva = new Reserva([
            'id' => 1,
            'estado' => 'pendiente',
        ]);

        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeEliminar')
            ->with(Rol::ADMIN)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reserva);

        $this->reservaRepository
            ->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                99,
                'reserva_eliminada'
            );

        $result = $this->reservaService->eliminar(
            1,
            99,
            Rol::ADMIN
        );

        $this->assertTrue($result);
    }

    public function test_it_throws_exception_when_non_admin_deletes_reserva(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeEliminar')
            ->with(1)
            ->willReturn(false);

        $this->reservaRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'Solo un administrador puede eliminar reservas'
        );

        $this->reservaService->eliminar(
            1,
            1,
            1
        );
    }

    public function test_it_throws_exception_when_deleting_non_existent_reserva(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeEliminar')
            ->with(Rol::ADMIN)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->reservaRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Reserva no encontrada'
        );

        $this->reservaService->eliminar(
            999,
            99,
            Rol::ADMIN
        );
    }

    public function test_it_can_restore_reserva_as_admin(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeRestaurar')
            ->with(Rol::ADMIN)
            ->willReturn(true);

        $reservaEliminada = new Reserva();
        $reservaEliminada->setAttribute('id', 1);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn($reservaEliminada);

        $this->reservaRepository
            ->expects($this->once())
            ->method('restore')
            ->with(1)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                99,
                'reserva_restaurada'
            );

        $result = $this->reservaService->restaurar(
            1,
            99,
            Rol::ADMIN
        );

        $this->assertTrue($result);
    }

    public function test_it_throws_exception_when_non_admin_restores_reserva(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeRestaurar')
            ->with(1)
            ->willReturn(false);

        $this->reservaRepository
            ->expects($this->never())
            ->method('restore');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'Solo un administrador puede restaurar reservas'
        );

        $this->reservaService->restaurar(
            1,
            1,
            1
        );
    }

    public function test_it_throws_exception_when_restoring_non_existent_reserva(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeRestaurar')
            ->with(Rol::ADMIN)
            ->willReturn(true);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(999)
            ->willReturn(null);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->reservaRepository
            ->expects($this->never())
            ->method('restore');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Reserva eliminada no encontrada'
        );

        $this->reservaService->restaurar(
            999,
            99,
            Rol::ADMIN
        );
    }

    public function test_it_throws_exception_when_restoring_active_reserva(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeRestaurar')
            ->with(Rol::ADMIN)
            ->willReturn(true);

        $reservaActiva = new Reserva();
        $reservaActiva->setAttribute('id', 1);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(1)
            ->willReturn(null);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($reservaActiva);

        $this->reservaRepository
            ->expects($this->never())
            ->method('restore');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage(
            'La reserva no está eliminada'
        );

        $this->reservaService->restaurar(
            1,
            99,
            Rol::ADMIN
        );
    }
}