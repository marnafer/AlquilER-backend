<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\ReservaController;
use App\Services\ReservaService;
use Tests\TestCase;

final class ReservaControllerTest extends TestCase
{
    private ReservaService $service;
    private ReservaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(ReservaService::class);
        $this->controller = new ReservaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );
    }

    public function test_index_pasa_los_permisos_y_los_filtros(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(5, 1, ['estado' => 'pendiente'])
            ->willReturn([['id' => 1]]);

        $response = $this->controller->index($this->makeRequest([
            'query' => ['estado' => 'pendiente'],
        ]));

        $this->assertSame(200, $response->status());
        $this->assertSame([['id' => 1]], $response->payload()['data']);
    }

    public function test_index_convierte_los_ids_a_entero(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(5, 1, ['propiedad_id' => 7, 'usuario_id' => 3])
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => ['propiedad_id' => '7', 'usuario_id' => '3'],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_convierte_los_flags_de_papelera(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(5, 1, [
                'solo_eliminados' => true,
                'incluir_eliminados' => false,
            ])
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => [
                'solo_eliminados' => '1',
                'incluir_eliminados' => '0',
            ],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_rechaza_a_un_no_autenticado(): void
    {
        $controller = new ReservaController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(\App\Exceptions\UnauthorizedException::class);

        $controller->index($this->makeRequest());
    }

    public function test_show_pasa_los_permisos_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(9, 5, 1)
            ->willReturn(['id' => 9]);

        $response = $this->controller->show($this->makeRequest(), 9);

        $this->assertSame(200, $response->status());
    }

    public function test_store_devuelve_el_id_creado(): void
    {
        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(['propiedad_id' => 7], 5)
            ->willReturn(55);

        $response = $this->controller->store($this->makeJsonRequest([
            'propiedad_id' => 7,
        ]));

        $this->assertSame(201, $response->status());
        $this->assertSame(['id' => 55], $response->payload()['data']);
        $this->assertSame(
            'Reserva creada correctamente',
            $response->payload()['message']
        );
    }

    public function test_store_admin_pasa_al_admin(): void
    {
        $controller = new ReservaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 2))
        );

        $this->service
            ->expects($this->once())
            ->method('crearDesdeAdmin')
            ->with(['propiedad_id' => 7], 5)
            ->willReturn(66);

        $response = $controller->storeAdmin(
            $this->makeJsonRequest(['propiedad_id' => 7])
        );

        $this->assertSame(201, $response->status());
        $this->assertSame(66, $response->payload()['data']['id']);
    }

    public function test_store_admin_rechaza_a_un_no_administrador(): void
    {
        $this->service->expects($this->never())->method('crearDesdeAdmin');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $this->controller->storeAdmin($this->makeJsonRequest([]));
    }

    public function test_transiciones_de_estado(): void
    {
        $casos = [
            ['confirm', 'confirmar', 'Reserva confirmada correctamente'],
            ['reject', 'rechazar', 'Reserva rechazada correctamente'],
            ['finalize', 'finalizar', 'Reserva finalizada correctamente'],
            ['cancel', 'cancelar', 'Reserva cancelada correctamente'],
        ];

        foreach ($casos as [$accion, $metodo, $mensaje]) {
            $this->service
                ->expects($this->once())
                ->method($metodo)
                ->with(9, 5, 1);

            $response = $this->controller->{$accion}(
                $this->makeRequest(),
                9
            );

            $this->assertSame(200, $response->status());
            $this->assertSame($mensaje, $response->payload()['message']);
        }
    }

    public function test_update_pasa_los_permisos_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(9, ['estado' => 'confirmada'], 5, 1);

        $response = $this->controller->update(
            $this->makeJsonRequest(['estado' => 'confirmada']),
            9
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Reserva actualizada correctamente',
            $response->payload()['message']
        );
    }

    public function test_delete_y_restore_pasa_los_permisos(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(9, 5, 1);
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(9, 5, 1);

        $this->assertSame(
            200,
            $this->controller->delete($this->makeRequest(), 9)->status()
        );
        $this->assertSame(
            200,
            $this->controller->restore($this->makeRequest(), 9)->status()
        );
    }
}
