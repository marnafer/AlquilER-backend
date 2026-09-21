<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\RolController;
use App\Services\RolService;
use App\Models\Rol;

class RolControllerTest extends TestCase
{
    private RolController $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(
            RolService::class
        );

        $this->controller = new RolController(
            $this->service
        );

        $this->actingAs(1, 2);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);

        parent::tearDown();
    }

    public function test_el_controlador_puede_listar_roles(): void
    {
        $roles = [
            ['id' => 1, 'nombre' => 'Administrador'],
            ['id' => 2, 'nombre' => 'Usuario']
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn($roles);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            $roles,
            $response['data']
        );

        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_un_rol(): void
    {
        $rol = new Rol([
            'id' => 1,
            'nombre' => 'Administrador'
        ]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($rol);

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertTrue($response['success']);

        $this->assertArrayHasKey(
            'data',
            $response
        );

        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_crear_un_rol(): void
    {
        $rol = new Rol([
            'id' => 5,
            'nombre' => 'Nuevo Rol'
        ]);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([
                'nombre' => 'Nuevo Rol'
            ])
            ->willReturn($rol);

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Nuevo Rol'
            ]),
            fn() => $this->controller->store()
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            'Rol creado exitosamente',
            $response['message']
        );

        $this->assertSame(201, http_response_code());
    }

    public function test_el_controlador_puede_actualizar_un_rol(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                [
                    'nombre' => 'Rol Actualizado'
                ]
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Rol Actualizado'
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            'Rol actualizado exitosamente',
            $response['message']
        );

        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_eliminar_un_rol(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1);

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            'Rol eliminado exitosamente',
            $response['message']
        );

        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_restaurar_un_rol(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1);

        $response = $this->captureJson(
            fn() => $this->controller->restore(1)
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            'Rol restaurado exitosamente',
            $response['message']
        );

        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_verifica_autenticacion_de_admin_al_crear(): void
    {
        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([
                'nombre' => 'Nuevo Rol'
            ])
            ->willReturn(
                new Rol([
                    'id' => 5,
                    'nombre' => 'Nuevo Rol'
                ])
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Nuevo Rol'
            ]),
            fn() => $this->controller->store()
        );

        $this->assertTrue($response['success']);
    }

    public function test_el_controlador_verifica_autenticacion_de_admin_al_actualizar(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                [
                    'nombre' => 'Rol Actualizado'
                ]
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Rol Actualizado'
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertTrue($response['success']);
    }

    public function test_el_controlador_verifica_autenticacion_de_admin_al_eliminar(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1);

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertTrue($response['success']);
    }

    public function test_el_controlador_verifica_autenticacion_de_admin_al_restaurar(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1);

        $response = $this->captureJson(
            fn() => $this->controller->restore(1)
        );

        $this->assertTrue($response['success']);
    }
}