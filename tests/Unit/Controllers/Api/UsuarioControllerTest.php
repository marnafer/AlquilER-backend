<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\UsuarioController;
use App\Models\Usuario;
use App\Services\UsuarioService;
use Tests\TestCase;

final class UsuarioControllerTest extends TestCase
{
    private UsuarioService $service;
    private UsuarioController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(UsuarioService::class);
        $this->controller = new UsuarioController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 2))
        );
    }

    public function test_index_lista_usuarios_como_admin(): void
    {
        $usuarios = [['id' => 1], ['id' => 2]];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn($usuarios);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame($usuarios, $response->payload()['data']);
    }

    public function test_index_pasa_los_filtros_de_papelera(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(['solo_eliminados' => true])
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => ['solo_eliminados' => '1'],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_pasa_el_filtro_incluir_eliminados(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(['incluir_eliminados' => false])
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => ['incluir_eliminados' => '0'],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_rechaza_a_un_no_administrador(): void
    {
        $controller = new UsuarioController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->index($this->makeRequest());
    }

    public function test_show_pasa_los_permisos_del_solicitante(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtenerAutorizado')
            ->with(9, 5, 2)
            ->willReturn(new Usuario(['id' => 9]));

        $response = $this->controller->show($this->makeRequest(), 9);

        $this->assertSame(200, $response->status());
    }

    public function test_profile_devuelve_el_perfil_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtenerConRol')
            ->with(5)
            ->willReturn(new Usuario(['id' => 5, 'email' => 'a@b.c']));

        $response = $this->controller->profile($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame('a@b.c', $response->payload()['data']['email']);
    }

    public function test_public_profile_pasa_los_permisos(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtenerPerfilPublico')
            ->with(9, 5, 2)
            ->willReturn([]);

        $response = $this->controller->publicProfile(
            $this->makeRequest(),
            9
        );

        $this->assertSame(200, $response->status());
    }

    public function test_store_admin_crea_un_usuario(): void
    {
        $this->service
            ->expects($this->once())
            ->method('crearDesdeAdmin')
            ->with(['email' => 'n@e.c'], 5)
            ->willReturn(new Usuario(['id' => 33]));

        $response = $this->controller->storeAdmin(
            $this->makeJsonRequest(['email' => 'n@e.c'])
        );

        $this->assertSame(201, $response->status());
        $this->assertSame(
            'Usuario creado correctamente',
            $response->payload()['message']
        );
    }

    public function test_store_admin_rechaza_a_un_no_administrador(): void
    {
        $controller = new UsuarioController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('crearDesdeAdmin');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->storeAdmin($this->makeJsonRequest(['email' => 'x']));
    }

    public function test_update_pasa_los_permisos_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(9, ['nombre' => 'Ana'], 5, 2);

        $response = $this->controller->update(
            $this->makeJsonRequest(['nombre' => 'Ana']),
            9
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Usuario actualizado correctamente',
            $response->payload()['message']
        );
    }

    public function test_delete_pasa_los_permisos_del_token(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(9, 5, 2);

        $response = $this->controller->delete($this->makeRequest(), 9);

        $this->assertSame(200, $response->status());
        $this->assertSame('Usuario eliminado', $response->payload()['message']);
    }

    public function test_restore_requiere_admin(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(9);

        $response = $this->controller->restore($this->makeRequest(), 9);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Usuario restaurado correctamente',
            $response->payload()['message']
        );
    }

    public function test_restore_rechaza_a_un_no_administrador(): void
    {
        $controller = new UsuarioController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('restaurar');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->restore($this->makeRequest(), 9);
    }
}
