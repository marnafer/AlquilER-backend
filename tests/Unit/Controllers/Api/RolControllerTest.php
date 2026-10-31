<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\RolController;
use App\Models\Rol;
use App\Services\RolService;
use Tests\TestCase;

final class RolControllerTest extends TestCase
{
    private RolService $service;
    private RolController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(RolService::class);
        $this->controller = new RolController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 2))
        );
    }

    public function test_index_lista_los_roles(): void
    {
        $roles = [
            ['id' => 1, 'nombre' => 'Administrador'],
            ['id' => 2, 'nombre' => 'Usuario'],
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn($roles);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertTrue($response->payload()['success']);
        $this->assertSame($roles, $response->payload()['data']);
    }

    public function test_index_pasa_los_filtros_de_papelera(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([
                'solo_eliminados' => true,
                'incluir_eliminados' => true,
            ])
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => [
                'solo_eliminados' => '1',
                'incluir_eliminados' => 'true',
            ],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_sin_filtros_papelera_devuelve_vacio(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn([]);

        $this->controller->index($this->makeRequest());

        $this->addToAssertionCount(1);
    }

    public function test_index_rechaza_a_un_no_administrador_en_papelera(): void
    {
        $controller = new RolController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->index($this->makeRequest([
            'query' => ['solo_eliminados' => '1'],
        ]));
    }

    public function test_show_devuelve_un_rol(): void
    {
        $rol = new Rol(['id' => 1, 'nombre' => 'Administrador']);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($rol);

        $response = $this->controller->show($this->makeRequest(), 1);

        $this->assertSame(200, $response->status());
        $this->assertSame($rol, $response->payload()['data']);
    }

    public function test_store_crea_un_rol_como_admin(): void
    {
        $rol = new Rol(['id' => 5, 'nombre' => 'Nuevo Rol']);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(['nombre' => 'Nuevo Rol'])
            ->willReturn($rol);

        $response = $this->controller->store($this->makeJsonRequest([
            'nombre' => 'Nuevo Rol',
        ]));

        $this->assertSame(201, $response->status());
        $this->assertSame($rol, $response->payload()['data']);
        $this->assertSame(
            'Rol creado exitosamente',
            $response->payload()['message']
        );
    }

    public function test_store_rechaza_a_un_no_administrador(): void
    {
        $controller = new RolController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('crear');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->store($this->makeJsonRequest(['nombre' => 'X']));
    }

    public function test_store_sin_token_rechaza(): void
    {
        $controller = new RolController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('crear');

        $this->expectException(\App\Exceptions\UnauthorizedException::class);

        $controller->store($this->makeJsonRequest(['nombre' => 'X']));
    }

    public function test_update_actualiza_un_rol(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, ['nombre' => 'Rol Actualizado']);

        $response = $this->controller->update(
            $this->makeJsonRequest(['nombre' => 'Rol Actualizado']),
            1
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Rol actualizado exitosamente',
            $response->payload()['message']
        );
    }

    public function test_delete_elimina_un_rol(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1);

        $response = $this->controller->delete($this->makeRequest(), 1);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Rol eliminado exitosamente',
            $response->payload()['message']
        );
    }

    public function test_restore_restaura_un_rol(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1);

        $response = $this->controller->restore($this->makeRequest(), 1);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Rol restaurado exitosamente',
            $response->payload()['message']
        );
    }
}
