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
use App\Policies\ReservaPolicy;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\ReservaRepositoryInterface;
use App\Services\LogActividadService;
use App\Services\NotificacionService;
use App\Services\ReservaService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

final class ReservaServiceTest extends TestCase
{
    private $reservaRepository;
    private $propiedadRepository;
    private $reservaPolicy;
    private $logService;
    private $notificacionService;
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

        $this->reservaService = new ReservaService(
            $this->reservaRepository,
            $this->propiedadRepository,
            $this->reservaPolicy,
            $this->logService,
            $this->notificacionService
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

        $result = $this->reservaService->listar(
            99,
            2,
            $filtros
        );

        $this->assertSame($reservas, $result);
    }

    public function test_it_can_list_own_reservas_and_reservas_of_owned_properties(): void
    {
        $reservasUsuario = [
            [
                'id' => 1,
                'usuario_id' => 1,
                'propiedad_id' => 10,
                'fecha_reserva' => '2026-09-15 10:00:00',
            ],
        ];

        $reservasPropiedad = [
            [
                'id' => 2,
                'usuario_id' => 5,
                'propiedad_id' => 20,
                'fecha_reserva' => '2026-09-16 10:00:00',
            ],
        ];

        $propiedad = new Propiedad([
            'usuario_id' => 1,
        ]);

        $propiedad->setAttribute('id', 20);

        $propiedades = new Collection([
            $propiedad,
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('getByUsuario')
            ->with(1)
            ->willReturn($reservasUsuario);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('porUsuario')
            ->with(1)
            ->willReturn($propiedades);

        $this->reservaRepository
            ->expects($this->once())
            ->method('getByPropiedad')
            ->with(20)
            ->willReturn($reservasPropiedad);

        $result = $this->reservaService->listar(
            1,
            1
        );

        $this->assertCount(2, $result);
        $this->assertSame(2, $result[0]['id']);
        $this->assertSame(1, $result[1]['id']);
    }

    public function test_it_removes_duplicate_reservas_when_listing(): void
    {
        $reservas = [
            [
                'id' => 1,
                'usuario_id' => 1,
                'propiedad_id' => 10,
                'fecha_reserva' => '2026-09-15 10:00:00',
            ],
        ];

        $this->reservaRepository
            ->expects($this->once())
            ->method('getByUsuario')
            ->with(1)
            ->willReturn($reservas);

        $propiedad = new Propiedad([
            'usuario_id' => 1,
        ]);

        $propiedad->setAttribute('id', 10);

        $this->propiedadRepository
            ->expects($this->once())
            ->method('porUsuario')
            ->with(1)
            ->willReturn(
                new Collection([$propiedad])
            );

        $this->reservaRepository
            ->expects($this->once())
            ->method('getByPropiedad')
            ->with(10)
            ->willReturn($reservas);

        $result = $this->reservaService->listar(
            1,
            1
        );

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);
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
                'fecha_inicio_alquiler' => '2026-10-01',
            ],
            1
        );

        $this->assertSame(20, $result);
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
                'fecha_inicio_alquiler' => '2026-10-01',
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
                'fecha_inicio_alquiler' => '2026-10-01',
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
                'fecha_inicio_alquiler' => '2026-10-01',
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
            2
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
            ->with(2)
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
            2
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
            ->with(2)
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
            2
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
            ->with(2)
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
            2
        );
    }

    public function test_it_throws_exception_when_updating_non_existent_reserva(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeModificar')
            ->with(2)
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
            2
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
            ->with(2)
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
            2
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
            ->with(2)
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
            2
        );
    }

    public function test_it_can_restore_reserva_as_admin(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeRestaurar')
            ->with(2)
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
            2
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
            ->with(2)
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
            2
        );
    }

    public function test_it_throws_exception_when_restoring_active_reserva(): void
    {
        $this->reservaPolicy
            ->expects($this->once())
            ->method('puedeRestaurar')
            ->with(2)
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
            2
        );
    }
}