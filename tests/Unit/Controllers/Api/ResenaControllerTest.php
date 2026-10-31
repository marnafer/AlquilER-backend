<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\ResenaController;
use App\Services\ResenaService;
use Tests\TestCase;

final class ResenaControllerTest extends TestCase
{
    private ResenaService $service;
    private ResenaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(ResenaService::class);
        $this->controller = new ResenaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 2))
        );
    }

    public function test_index_devuelve_con_mensaje(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn([['id' => 1]]);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Reseñas obtenidas correctamente',
            $response->payload()['message']
        );
    }

    public function test_index_reconstruye_los_filtros_con_casting(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([
                'tipo' => 'inquilino',
                'fecha_desde' => '2024-01-01',
                'calificacion' => 5,
                'calificacion_min' => 3,
                'propiedad_id' => 7,
                'solo_eliminados' => true,
            ])
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => [
                'tipo' => 'inquilino',
                'fecha_desde' => '2024-01-01',
                'calificacion' => '5',
                'calificacion_min' => '3',
                'propiedad_id' => '7',
                'solo_eliminados' => '1',
            ],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_rechaza_papelera_a_un_no_administrador(): void
    {
        $controller = new ResenaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->index($this->makeRequest([
            'query' => ['solo_eliminados' => '1'],
        ]));
    }

    public function test_show_devuelve_una_resena(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(7)
            ->willReturn(new \App\Models\Resena(['id' => 7]));

        $response = $this->controller->show($this->makeRequest(), 7);

        $this->assertSame(200, $response->status());
    }

    public function test_get_by_propiedad_incluye_el_promedio(): void
    {
        $coleccion = new \Illuminate\Database\Eloquent\Collection([['id' => 1], ['id' => 2]]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPorPropiedad')
            ->with(7)
            ->willReturn($coleccion);

        $this->service
            ->expects($this->once())
            ->method('promedioPropiedad')
            ->with(7)
            ->willReturn(4.5);

        $response = $this->controller->getByPropiedad(
            $this->makeRequest(),
            7
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(2, $response->payload()['data']['total']);
        $this->assertSame(4.5, $response->payload()['data']['promedio']);
    }

    public function test_get_by_usuario_incluye_el_promedio(): void
    {
        $coleccion = new \Illuminate\Database\Eloquent\Collection([['id' => 1]]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPorUsuario')
            ->with(9)
            ->willReturn($coleccion);

        $this->service
            ->expects($this->once())
            ->method('promedioUsuario')
            ->with(9)
            ->willReturn(3.0);

        $response = $this->controller->getByUsuario($this->makeRequest(), 9);

        $this->assertSame(3.0, $response->payload()['data']['promedio']);
    }

    public function test_get_by_reserva_y_calificador_no_requieren_auth(): void
    {
        $controller = new ResenaController(
            $this->service,
            $this->authenticator()
        );

        $this->service
            ->expects($this->once())
            ->method('obtenerPorReserva')
            ->with(3)
            ->willReturn(new \Illuminate\Database\Eloquent\Collection([]));
        $this->service
            ->expects($this->once())
            ->method('obtenerPorCalificador')
            ->with(9)
            ->willReturn(new \Illuminate\Database\Eloquent\Collection([]));

        $this->assertSame(
            200,
            $controller->getByReserva($this->makeRequest(), 3)->status()
        );
        $this->assertSame(
            200,
            $controller->getByCalificador($this->makeRequest(), 9)->status()
        );
    }

    public function test_get_my_properties_reviews_usa_al_token(): void
    {
        $coleccion = new \Illuminate\Database\Eloquent\Collection([['id' => 1]]);

        $this->service
            ->expects($this->once())
            ->method('obtenerResenasDeMisPropiedades')
            ->with(5)
            ->willReturn($coleccion);

        $response = $this->controller->getMyPropertiesReviews(
            $this->makeRequest()
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(1, $response->payload()['data']['total']);
    }

    public function test_get_my_tenant_reviews_usa_al_token(): void
    {
        $coleccion = new \Illuminate\Database\Eloquent\Collection([]);

        $this->service
            ->expects($this->once())
            ->method('obtenerMisResenasComoInquilino')
            ->with(5)
            ->willReturn($coleccion);

        $response = $this->controller->getMyTenantReviews(
            $this->makeRequest()
        );

        $this->assertSame(200, $response->status());
    }

    public function test_store_crea_desde_el_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(['propiedad_id' => 7, 'calificacion' => 5], 5)
            ->willReturn(new \App\Models\Resena(['id' => 21]));

        $response = $this->controller->store($this->makeJsonRequest([
            'propiedad_id' => 7,
            'calificacion' => 5,
        ]));

        $this->assertSame(201, $response->status());
        $this->assertSame(
            'Reseña creada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_store_admin_pasa_al_admin(): void
    {
        $this->service
            ->expects($this->once())
            ->method('crearDesdeAdmin')
            ->with(['propiedad_id' => 7], 5)
            ->willReturn(new \App\Models\Resena(['id' => 22]));

        $response = $this->controller->storeAdmin(
            $this->makeJsonRequest(['propiedad_id' => 7])
        );

        $this->assertSame(201, $response->status());
    }

    public function test_store_admin_rechaza_a_un_no_administrador(): void
    {
        $controller = new ResenaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('crearDesdeAdmin');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->storeAdmin($this->makeJsonRequest([]));
    }

    public function test_update_pasa_los_permisos_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(21, ['calificacion' => 4], 5, 2);

        $response = $this->controller->update(
            $this->makeJsonRequest(['calificacion' => 4]),
            21
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Reseña actualizada correctamente',
            $response->payload()['message']
        );
    }

    public function test_delete_y_restore_pasa_los_permisos(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(21, 5, 2);
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(21, 5, 2);

        $this->assertSame(
            200,
            $this->controller->delete($this->makeRequest(), 21)->status()
        );
        $this->assertSame(
            200,
            $this->controller->restore($this->makeRequest(), 21)->status()
        );
    }
}
