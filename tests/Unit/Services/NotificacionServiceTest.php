<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Models\Notificacion;
use App\Repositories\NotificacionRepositoryInterface;
use App\Services\NotificacionService;
use PHPUnit\Framework\TestCase;

final class NotificacionServiceTest extends TestCase
{
    private $repository;
    private NotificacionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(
            NotificacionRepositoryInterface::class
        );

        $this->service = new NotificacionService(
            $this->repository
        );
    }

    public function test_listar_por_usuario_devuelve_las_notificaciones(): void
    {
        $items = [
            ['id' => 1, 'titulo' => 'Reserva confirmada', 'leida' => 0],
            ['id' => 2, 'titulo' => 'Nueva consulta', 'leida' => 1],
        ];

        $this->repository
            ->expects($this->once())
            ->method('getByUsuario')
            ->with(5)
            ->willReturn($items);

        $resultado = $this->service->listarPorUsuario(5);

        $this->assertSame($items, $resultado);
    }

    public function test_contar_no_leidas_devuelve_el_conteo(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('contarNoLeidas')
            ->with(5)
            ->willReturn(3);

        $this->assertSame(3, $this->service->contarNoLeidas(5));
    }

    public function test_crear_notifica_al_usuario(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $data): bool {
                return
                    $data['usuario_id'] === 5 &&
                    $data['tipo'] === 'consulta_nueva' &&
                    $data['titulo'] === 'Nueva consulta' &&
                    $data['mensaje'] === 'Alguien consultó tu propiedad' &&
                    $data['referencia_id'] === 10 &&
                    $data['leida'] === 0 &&
                    isset($data['fecha_notificacion']);
            }));

        $this->service->crear(
            5,
            'consulta_nueva',
            'Nueva consulta',
            'Alguien consultó tu propiedad',
            10
        );
    }

    public function test_marcar_leida_marca_correctamente(): void
    {
        $notificacion = new Notificacion([
            'id' => 1,
            'usuario_id' => 5,
        ]);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($notificacion);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with(
                1,
                ['leida' => 1]
            )
            ->willReturn(true);

        $resultado = $this->service->marcarLeida(1, 5);

        $this->assertTrue($resultado);
    }

    public function test_marcar_leida_lanza_not_found_si_el_id_es_invalido(): void
    {
        $this->repository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Notificación no encontrada');

        $this->service->marcarLeida('abc', 5);
    }

    public function test_marcar_leida_lanza_not_found_si_la_notificacion_no_existe(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->repository
            ->expects($this->never())
            ->method('update');

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Notificación no encontrada');

        $this->service->marcarLeida(999, 5);
    }

    public function test_marcar_leida_lanza_forbidden_si_la_notificacion_no_es_del_usuario(): void
    {
        $notificacion = new Notificacion([
            'id' => 1,
            'usuario_id' => 7,
        ]);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($notificacion);

        $this->repository
            ->expects($this->never())
            ->method('update');

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'No tienes permiso para esta notificación'
        );

        $this->service->marcarLeida(1, 5);
    }

    public function test_marcar_todas_leidas_marca_todas_las_del_usuario(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('marcarTodasLeidas')
            ->with(5)
            ->willReturn(true);

        $this->assertTrue($this->service->marcarTodasLeidas(5));
    }
}