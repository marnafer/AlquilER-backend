<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Resena;
use App\Models\Reserva;
use App\Policies\ResenaPolicy;
use App\Repositories\ResenaRepositoryInterface;
use App\Repositories\ReservaRepositoryInterface;
use App\Services\LogActividadService;
use App\Services\ResenaService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

final class ResenaServiceTest extends TestCase
{
    private $resenaRepository;
    private $reservaRepository;
    private $resenaPolicy;
    private $logService;
    private $resenaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resenaRepository = $this->createMock(
            ResenaRepositoryInterface::class
        );

        $this->reservaRepository = $this->createMock(
            ReservaRepositoryInterface::class
        );

        $this->resenaPolicy = $this->createMock(
            ResenaPolicy::class
        );

        $this->logService = $this->createMock(
            LogActividadService::class
        );

        $this->resenaService = new ResenaService(
            $this->resenaRepository,
            $this->reservaRepository,
            $this->resenaPolicy,
            $this->logService
        );
    }

    public function test_it_can_list_resenas(): void
    {
        $filtros = [
            'tipo' => 'propiedad',
        ];

        $resenas = new Collection([
            new Resena([
                'id' => 1,
                'tipo' => 'propiedad',
                'calificacion' => 5,
            ]),
        ]);

        $this->resenaRepository
            ->expects($this->once())
            ->method('all')
            ->with($filtros)
            ->willReturn($resenas);

        $result = $this->resenaService->listar($filtros);

        $this->assertSame($resenas, $result['items']);
        $this->assertSame(1, $result['total']);
    }

    public function test_it_can_get_resena_by_id(): void
    {
        $resena = new Resena([
            'id' => 1,
            'calificacion' => 5,
        ]);

        $this->resenaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($resena);

        $result = $this->resenaService->obtener(1);

        $this->assertSame($resena, $result);
    }

    public function test_it_throws_exception_when_resena_not_found(): void
    {
        $this->resenaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Reseña no encontrada');

        $this->resenaService->obtener(999);
    }

    public function test_it_can_get_resenas_by_reserva(): void
    {
        $reserva = new Reserva([
            'id' => 5,
        ]);

        $resenas = new Collection([
            new Resena([
                'id' => 1,
                'reserva_id' => 5,
            ]),
            new Resena([
                'id' => 2,
                'reserva_id' => 5,
            ]),
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($reserva);

        $this->resenaRepository
            ->expects($this->once())
            ->method('getByReserva')
            ->with(5)
            ->willReturn($resenas);

        $result = $this->resenaService->obtenerPorReserva(5);

        $this->assertSame($resenas, $result);
        $this->assertCount(2, $result);
    }

    public function test_it_throws_exception_when_reserva_not_found(): void
    {
        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Reserva no encontrada');

        $this->resenaService->obtenerPorReserva(999);
    }

    public function test_it_can_get_resenas_by_propiedad(): void
    {
        $resenas = new Collection([
            new Resena([
                'id' => 1,
                'tipo' => 'propiedad',
            ]),
        ]);

        $this->resenaRepository
            ->expects($this->once())
            ->method('getByPropiedad')
            ->with(10)
            ->willReturn($resenas);

        $result = $this->resenaService->obtenerPorPropiedad(10);

        $this->assertSame($resenas, $result);
    }

    public function test_it_can_get_resenas_by_usuario(): void
    {
        $resenas = new Collection([
            new Resena([
                'id' => 1,
                'tipo' => 'inquilino',
            ]),
        ]);

        $this->resenaRepository
            ->expects($this->once())
            ->method('getByUsuario')
            ->with(5)
            ->willReturn($resenas);

        $result = $this->resenaService->obtenerPorUsuario(5);

        $this->assertSame($resenas, $result);
    }

    public function test_it_can_get_resenas_by_calificador(): void
    {
        $resenas = new Collection([
            new Resena([
                'id' => 1,
                'calificador_id' => 5,
            ]),
        ]);

        $this->resenaRepository
            ->expects($this->once())
            ->method('getByCalificador')
            ->with(5)
            ->willReturn($resenas);

        $result = $this->resenaService->obtenerPorCalificador(5);

        $this->assertSame($resenas, $result);
    }

    public function test_it_can_create_resena_de_propiedad(): void
    {
        $reserva = new Reserva([
            'id' => 5,
            'estado' => 'finalizada',
            'usuario_id' => 2,
            'propiedad_id' => 1,
        ]);

        $resena = new Resena([
            'id' => 10,
            'reserva_id' => 5,
            'tipo' => 'propiedad',
            'calificador_id' => 2,
            'calificacion' => 5,
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($reserva);

        $this->resenaPolicy
            ->expects($this->once())
            ->method('crear')
            ->with(
                $reserva,
                2,
                'propiedad'
            );

        $this->resenaRepository
            ->expects($this->once())
            ->method('existePorReservaYTipo')
            ->with(5, 'propiedad')
            ->willReturn(false);

        $this->resenaRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                function (array $data): bool {
                    return
                        $data['reserva_id'] === 5 &&
                        $data['tipo'] === 'propiedad' &&
                        $data['calificacion'] === 5 &&
                        $data['calificador_id'] === 2;
                }
            ))
            ->willReturn($resena);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                2,
                'Creación de reseña'
            );

        $result = $this->resenaService->crear(
            [
                'reserva_id' => 5,
                'tipo' => 'propiedad',
                'calificacion' => 5,
            ],
            2
        );

        $this->assertSame($resena, $result);
    }

    public function test_it_can_create_resena_de_inquilino(): void
    {
        $reserva = new Reserva([
            'id' => 5,
            'estado' => 'finalizada',
            'usuario_id' => 2,
            'propiedad_id' => 1,
        ]);

        $resena = new Resena([
            'id' => 11,
            'reserva_id' => 5,
            'tipo' => 'inquilino',
            'calificador_id' => 9,
            'calificacion' => 4,
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($reserva);

        $this->resenaPolicy
            ->expects($this->once())
            ->method('crear')
            ->with(
                $reserva,
                9,
                'inquilino'
            );

        $this->resenaRepository
            ->expects($this->once())
            ->method('existePorReservaYTipo')
            ->with(5, 'inquilino')
            ->willReturn(false);

        $this->resenaRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                function (array $data): bool {
                    return
                        $data['reserva_id'] === 5 &&
                        $data['tipo'] === 'inquilino' &&
                        $data['calificacion'] === 4 &&
                        $data['calificador_id'] === 9;
                }
            ))
            ->willReturn($resena);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                9,
                'Creación de reseña'
            );

        $result = $this->resenaService->crear(
            [
                'reserva_id' => 5,
                'tipo' => 'inquilino',
                'calificacion' => 4,
            ],
            9
        );

        $this->assertSame($resena, $result);
    }

    public function test_it_throws_exception_when_reserva_does_not_exist(): void
    {
        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Reserva no encontrada');

        $this->resenaService->crear(
            [
                'reserva_id' => 999,
                'tipo' => 'propiedad',
                'calificacion' => 5,
            ],
            2
        );
    }

    public function test_it_throws_exception_when_reserva_is_not_finalizada(): void
    {
        $reserva = new Reserva([
            'id' => 5,
            'estado' => 'pendiente',
            'usuario_id' => 2,
            'propiedad_id' => 1,
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($reserva);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(
            'Solo se puede crear una reseña para una reserva finalizada'
        );

        $this->resenaService->crear(
            [
                'reserva_id' => 5,
                'tipo' => 'propiedad',
                'calificacion' => 5,
            ],
            2
        );
    }

    public function test_it_throws_exception_when_duplicate_resena(): void
    {
        $reserva = new Reserva([
            'id' => 5,
            'estado' => 'finalizada',
            'usuario_id' => 2,
            'propiedad_id' => 1,
        ]);

        $this->reservaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($reserva);

        $this->resenaPolicy
            ->expects($this->once())
            ->method('crear')
            ->with(
                $reserva,
                2,
                'propiedad'
            );

        $this->resenaRepository
            ->expects($this->once())
            ->method('existePorReservaYTipo')
            ->with(5, 'propiedad')
            ->willReturn(true);

        $this->resenaRepository
            ->expects($this->never())
            ->method('create');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage(
            'Ya existe una reseña de este tipo para la reserva'
        );

        $this->resenaService->crear(
            [
                'reserva_id' => 5,
                'tipo' => 'propiedad',
                'calificacion' => 5,
            ],
            2
        );
    }

    public function test_it_throws_validation_exception_when_create_data_is_invalid(): void
    {
        $this->reservaRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(ValidationException::class);

        $this->resenaService->crear(
            [
                'reserva_id' => null,
                'tipo' => 'incorrecto',
                'calificacion' => 10,
            ],
            2
        );
    }

    public function test_it_can_delete_own_resena(): void
    {
        $resena = new Resena([
            'id' => 10,
            'calificador_id' => 2,
        ]);

        $this->resenaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($resena);

        $this->resenaPolicy
            ->expects($this->once())
            ->method('eliminar')
            ->with(
                $resena,
                2,
                1
            );

        $this->resenaRepository
            ->expects($this->once())
            ->method('delete')
            ->with($resena)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                2,
                'Eliminación de reseña'
            );

        $this->resenaService->eliminar(
            10,
            2,
            1
        );

        $this->addToAssertionCount(1);
    }

    public function test_it_can_delete_any_resena_as_admin(): void
    {
        $resena = new Resena([
            'id' => 10,
            'calificador_id' => 5,
        ]);

        $this->resenaRepository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($resena);

        $this->resenaPolicy
            ->expects($this->once())
            ->method('eliminar')
            ->with(
                $resena,
                99,
                2
            );

        $this->resenaRepository
            ->expects($this->once())
            ->method('delete')
            ->with($resena)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                99,
                'Eliminación de reseña'
            );

        $this->resenaService->eliminar(
            10,
            99,
            2
        );

        $this->addToAssertionCount(1);
    }

    public function test_it_can_restore_resena_as_admin(): void
    {
        $resena = new Resena([
            'id' => 10,
        ]);

        $this->resenaRepository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(10)
            ->willReturn($resena);

        $this->resenaPolicy
            ->expects($this->once())
            ->method('restaurar')
            ->with(2);

        $this->resenaRepository
            ->expects($this->once())
            ->method('restore')
            ->with($resena)
            ->willReturn(true);

        $this->logService
            ->expects($this->once())
            ->method('registrar')
            ->with(
                99,
                'Restauración de reseña'
            );

        $this->resenaService->restaurar(
            10,
            99,
            2
        );

        $this->addToAssertionCount(1);
    }

    public function test_it_throws_exception_when_deleted_resena_does_not_exist(): void
    {
        $this->resenaRepository
            ->expects($this->once())
            ->method('findDeletedById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(
            'Reseña eliminada no encontrada'
        );

        $this->resenaService->restaurar(
            999,
            99,
            2
        );
    }

    public function test_it_can_get_promedio_by_propiedad(): void
    {
        $this->resenaRepository
            ->expects($this->once())
            ->method('getPromedioByPropiedad')
            ->with(1)
            ->willReturn(4.5);

        $result = $this->resenaService->promedioPropiedad(1);

        $this->assertSame(4.5, $result);
    }

    public function test_it_can_get_promedio_by_usuario(): void
    {
        $this->resenaRepository
            ->expects($this->once())
            ->method('getPromedioByUsuario')
            ->with(2)
            ->willReturn(4.2);

        $result = $this->resenaService->promedioUsuario(2);

        $this->assertSame(4.2, $result);
    }
}