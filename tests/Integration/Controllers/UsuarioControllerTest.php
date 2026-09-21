<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\UsuarioController;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Usuario;
use App\Services\UsuarioService;
use Tests\TestCase;

final class UsuarioControllerTest extends TestCase
    {
        private $service;
        private UsuarioController $controller;

        protected function setUp(): void
    {
        parent::setUp();

        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        $this->service = $this->createMock(
            UsuarioService::class
        );

        $this->controller = new UsuarioController(
            $this->service
        );
    }

    protected function tearDown(): void
    {
        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        parent::tearDown();
    }

    public function test_index_devuelve_los_usuarios(): void
    {
        $resultado = [
            'items' => [],
            'total' => 0,
        ];

        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn($resultado);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            $resultado,
            $response['data']
        );
    }

    public function test_index_requiere_ser_admin(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('listar');

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Solo administradores',
            $response['error']
        );
    }

    public function test_index_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('listar');

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_show_devuelve_un_usuario(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'email' => 'ana@example.com',
        ]);

        $usuario->id = 1;

        $this->actingAs(1, 1);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($usuario);

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Ana',
            $response['data']['nombre']
        );
        $this->assertSame(
            1,
            $response['data']['id']
        );
    }

    public function test_show_devuelve_not_found_si_no_existe(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(
                new NotFoundException(
                    'Usuario no encontrado'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->show(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Usuario no encontrado',
            $response['error']
        );
    }

    public function test_show_permite_a_un_admin_ver_cualquier_usuario(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
        ]);

        $usuario->id = 5;

        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(5)
            ->willReturn($usuario);

        $response = $this->captureJson(
            fn() => $this->controller->show(5)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            5,
            $response['data']['id']
        );
    }

    public function test_show_no_permite_ver_otro_usuario(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('obtener');

        $response = $this->captureJson(
            fn() => $this->controller->show(5)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No autorizado',
            $response['error']
        );
    }

    public function test_profile_devuelve_el_usuario_autenticado_con_rol(): void
    {
        $usuario = new Usuario([
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
        ]);

        $usuario->id = 1;

        $this->actingAs(1, 1);

        $this->service
            ->expects($this->once())
            ->method('obtenerConRol')
            ->with(1)
            ->willReturn($usuario);

        $response = $this->captureJson(
            fn() => $this->controller->profile()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Ana',
            $response['data']['nombre']
        );
    }

    public function test_profile_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('obtenerConRol');

        $response = $this->captureJson(
            fn() => $this->controller->profile()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_update_actualiza_un_usuario(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                [
                    'nombre' => 'Nombre Actualizado',
                ]
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Nombre Actualizado',
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Usuario actualizado correctamente',
            $response['message']
        );
    }

    public function test_update_no_permite_actualizar_otro_usuario(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('actualizar');

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Cambio',
            ]),
            fn() => $this->controller->update(5)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No autorizado',
            $response['error']
        );
    }

    public function test_update_permite_al_admin_actualizar_cualquier_usuario(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                5,
                [
                    'nombre' => 'Cambio',
                ]
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Cambio',
            ]),
            fn() => $this->controller->update(5)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Usuario actualizado correctamente',
            $response['message']
        );
    }

    public function test_update_devuelve_error_de_validacion(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                [
                    'nombre' => 'A',
                ]
            )
            ->willThrowException(
                new ValidationException([
                    'nombre' => [
                        'El nombre debe tener entre 2 y 50 caracteres'
                    ]
                ])
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'A',
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'El nombre debe tener entre 2 y 50 caracteres',
            $response['validation_errors']['nombre'][0]
        );
    }

    public function test_update_devuelve_error_de_conflicto(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                [
                    'email' => 'otro@example.com',
                ]
            )
            ->willThrowException(
                new ConflictException(
                    'El email ya está registrado'
                )
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'email' => 'otro@example.com',
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'El email ya está registrado',
            $response['error']
        );
    }

    public function test_delete_elimina_un_usuario(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1);

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Usuario eliminado',
            $response['message']
        );
    }

    public function test_delete_no_permite_eliminar_otro_usuario(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('eliminar');

        $response = $this->captureJson(
            fn() => $this->controller->delete(5)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No autorizado',
            $response['error']
        );
    }

    public function test_delete_permite_al_admin_eliminar_cualquier_usuario(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(5);

        $response = $this->captureJson(
            fn() => $this->controller->delete(5)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Usuario eliminado',
            $response['message']
        );
    }

    public function test_delete_devuelve_not_found_si_no_existe(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1)
            ->willThrowException(
                new NotFoundException(
                    'Usuario no encontrado'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Usuario no encontrado',
            $response['error']
        );
    }

    public function test_restore_restaura_un_usuario(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1);

        $response = $this->captureJson(
            fn() => $this->controller->restore(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Usuario restaurado correctamente',
            $response['message']
        );
    }

    public function test_restore_requiere_ser_admin(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('restaurar');

        $response = $this->captureJson(
            fn() => $this->controller->restore(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Solo administradores',
            $response['error']
        );
    }

    public function test_restore_devuelve_not_found_si_no_existe(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(999)
            ->willThrowException(
                new NotFoundException(
                    'Usuario eliminado no encontrado'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->restore(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Usuario eliminado no encontrado',
            $response['error']
        );
    }

    public function test_restore_devuelve_error_de_conflicto(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1)
            ->willThrowException(
                new ConflictException(
                    'Ya existe un usuario activo con ese email'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->restore(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Ya existe un usuario activo con ese email',
            $response['error']
        );
    }
}