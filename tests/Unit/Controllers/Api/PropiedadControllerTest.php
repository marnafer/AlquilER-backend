<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\PropiedadController;
use App\Models\Propiedad;
use App\Services\PropiedadService;
use Tests\TestCase;

final class PropiedadControllerTest extends TestCase
{
    private PropiedadService $service;
    private PropiedadController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(PropiedadService::class);
        $this->controller = new PropiedadController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );
    }

    public function test_index_pasa_los_filtros_publicos(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(['ciudad' => 'Cordoba'])
            ->willReturn([['id' => 1]]);

        $response = $this->controller->index($this->makeRequest([
            'query' => ['ciudad' => 'Cordoba'],
        ]));

        $this->assertSame(200, $response->status());
        $this->assertSame([['id' => 1]], $response->payload()['data']);
    }

    public function test_index_sin_filtros_devuelve_vacio(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn([]);

        $this->controller->index($this->makeRequest());

        $this->addToAssertionCount(1);
    }

    public function test_index_no_requiere_autenticacion(): void
    {
        $controller = new PropiedadController(
            $this->service,
            $this->authenticator()
        );

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn([]);

        $controller->index($this->makeRequest());

        $this->addToAssertionCount(1);
    }

    public function test_destacadas_no_requiere_autenticacion(): void
    {
        $controller = new PropiedadController(
            $this->service,
            $this->authenticator()
        );

        $this->service
            ->expects($this->once())
            ->method('listarDestacadas')
            ->willReturn([['id' => 2]]);

        $response = $controller->destacadas($this->makeRequest());

        $this->assertSame(200, $response->status());
    }

    public function test_admin_index_requiere_admin(): void
    {
        $controller = new PropiedadController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 2))
        );

        $this->service
            ->expects($this->once())
            ->method('listarParaAdmin')
            ->with([])
            ->willReturn([['id' => 1]]);

        $response = $controller->adminIndex($this->makeRequest());

        $this->assertSame(200, $response->status());
    }

    public function test_admin_index_rechaza_a_un_no_administrador(): void
    {
        $this->service->expects($this->never())->method('listarParaAdmin');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $this->controller->adminIndex($this->makeRequest());
    }

    public function test_mis_propiedades_usa_al_usuario_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('misPropiedades')
            ->with(5)
            ->willReturn([['id' => 1]]);

        $response = $this->controller->misPropiedades($this->makeRequest());

        $this->assertSame(200, $response->status());
    }

    public function test_show_no_requiere_autenticacion(): void
    {
        $controller = new PropiedadController(
            $this->service,
            $this->authenticator()
        );

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(4)
            ->willReturn(new Propiedad(['id' => 4]));

        $response = $controller->show($this->makeRequest(), 4);

        $this->assertSame(200, $response->status());
    }

    public function test_store_inyecta_al_creador_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(['titulo' => 'Casa'], 5, 1)
            ->willReturn(new Propiedad(['id' => 12]));

        $response = $this->controller->store($this->makeJsonRequest([
            'titulo' => 'Casa',
        ]));

        $this->assertSame(201, $response->status());
        $this->assertSame(
            'Propiedad creada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_update_pasa_los_permisos_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(5, 1, 12, ['titulo' => 'Editada']);

        $response = $this->controller->update(
            $this->makeJsonRequest(['titulo' => 'Editada']),
            12
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Propiedad actualizada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_delete_pasa_los_permisos_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(5, 1, 12);

        $response = $this->controller->delete($this->makeRequest(), 12);

        $this->assertSame(200, $response->status());
    }

    public function test_restore_pasa_los_permisos_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(5, 1, 12);

        $response = $this->controller->restore($this->makeRequest(), 12);

        $this->assertSame(200, $response->status());
    }

    public function test_store_requiere_autenticacion(): void
    {
        $controller = new PropiedadController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('crear');

        $this->expectException(\App\Exceptions\UnauthorizedException::class);

        $controller->store($this->makeJsonRequest(['titulo' => 'x']));
    }
}
