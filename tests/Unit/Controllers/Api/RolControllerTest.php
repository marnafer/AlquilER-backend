<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\RolController;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Models\Rol;
use App\Services\RolService;
use Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class RolControllerTest extends TestCase
{
    /** @var RolService&MockObject */
    private $service;
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

    public function test_index_lista_los_roles_como_admin(): void
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

    public function test_index_pasa_los_filtros_de_papelera_como_admin(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([
                'solo_eliminados' => true,
                'incluir_eliminados' => true,
            ])
            ->willReturn([]);

        $response = $this->controller->index($this->makeRequest([
            'query' => [
                'solo_eliminados' => '1',
                'incluir_eliminados' => 'true',
            ],
        ]));

        $this->assertSame(200, $response->status());
    }

    public function test_index_rechaza_a_un_no_administrador(): void
    {
        $controller = new RolController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(ForbiddenException::class);

        $controller->index($this->makeRequest());
    }

    public function test_index_sin_token_rechaza(): void
    {
        $controller = new RolController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(UnauthorizedException::class);

        $controller->index($this->makeRequest());
    }

    public function test_show_devuelve_un_rol_como_admin(): void
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

    public function test_show_rechaza_a_un_no_administrador(): void
    {
        $controller = new RolController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('obtener');

        $this->expectException(ForbiddenException::class);

        $controller->show($this->makeRequest(), 1);
    }

    public function test_show_sin_token_rechaza(): void
    {
        $controller = new RolController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('obtener');

        $this->expectException(UnauthorizedException::class);

        $controller->show($this->makeRequest(), 1);
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

        $this->expectException(ForbiddenException::class);

        $controller->store($this->makeJsonRequest(['nombre' => 'X']));
    }

    public function test_store_sin_token_rechaza(): void
    {
        $controller = new RolController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('crear');

        $this->expectException(UnauthorizedException::class);

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