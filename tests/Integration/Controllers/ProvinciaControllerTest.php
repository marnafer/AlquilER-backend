<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\ProvinciaController;
use App\Services\ProvinciaService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;
use App\Exceptions\ValidationException;
use App\Exceptions\BadRequestException;
use App\Models\Provincia;

class ProvinciaControllerTest extends TestCase
{
    private $controller;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(ProvinciaService::class);
        $this->controller = new ProvinciaController($this->service);
    }

    // =========================================================
    // INDEX
    // =========================================================

    public function test_index_lista_las_provincias_correctamente(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn([
                'items' => [],
                'total' => 0
            ]);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']['items']);
        $this->assertSame(0, $response['data']['total']);
    }

    // =========================================================
    // SHOW
    // =========================================================

    public function test_show_obtiene_una_provincia_correctamente(): void
    {
        $provincia = new Provincia([
            'nombre' => 'Entre Ríos'
        ]);

        $provincia->id = 1;

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($provincia);

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame('Entre Ríos', $response['data']['nombre']);
        $this->assertSame(1, $response['data']['id']);
    }

    public function test_show_devuelve_not_found_si_no_existe(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(
                new NotFoundException(
                    'Provincia no encontrada'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->show(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Provincia no encontrada',
            $response['error']
        );
    }

    public function test_show_devuelve_validation_error_si_el_id_es_invalido(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with('abc')
            ->willThrowException(
                new ValidationException([
                    'id' => [
                        'El ID de provincia debe ser numérico'
                    ]
                ])
            );

        $response = $this->captureJson(
            fn() => $this->controller->show('abc')
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Error de validación',
            $response['error']
        );
        $this->assertSame(
            [
                'El ID de provincia debe ser numérico'
            ],
            $response['validation_errors']['id']
        );
    }

    // =========================================================
    // STORE
    // =========================================================

    public function test_store_crea_una_provincia_correctamente(): void
    {
        $this->actingAs(1, 2);

        $provincia = new Provincia([
            'nombre' => 'Entre Ríos'
        ]);

        $provincia->id = 1;

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([
                'nombre' => 'Entre Ríos'
            ])
            ->willReturn($provincia);

        $response = [];

        $this->withJsonBody(
            json_encode([
                'nombre' => 'Entre Ríos'
            ]),
            function () use (&$response) {
                $response = $this->captureJson(
                    fn() => $this->controller->store()
                );
            }
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Entre Ríos',
            $response['data']['nombre']
        );
        $this->assertSame(1, $response['data']['id']);
        $this->assertSame(
            'Provincia creada exitosamente',
            $response['message']
        );
    }

    public function test_store_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('crear');

        $response = $this->captureJson(
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token inválido o expirado',
            $response['error']
        );
    }

    public function test_store_requiere_rol_admin(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('crear');

        $response = [];

        $this->withJsonBody(
            json_encode([
                'nombre' => 'Entre Ríos'
            ]),
            function () use (&$response) {
                $response = $this->captureJson(
                    fn() => $this->controller->store()
                );
            }
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Solo administradores',
            $response['error']
        );
    }

    public function test_store_devuelve_validation_error_si_los_datos_son_invalidos(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([])
            ->willThrowException(
                new ValidationException([
                    'nombre' => [
                        'El nombre es requerido'
                    ]
                ])
            );

        $response = [];

        $this->withJsonBody(
            json_encode([]),
            function () use (&$response) {
                $response = $this->captureJson(
                    fn() => $this->controller->store()
                );
            }
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Error de validación',
            $response['error']
        );
        $this->assertSame(
            [
                'El nombre es requerido'
            ],
            $response['validation_errors']['nombre']
        );
    }

    public function test_store_devuelve_conflict_si_la_provincia_ya_existe(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([
                'nombre' => 'Entre Ríos'
            ])
            ->willThrowException(
                new ConflictException(
                    'Provincia existente, no se puede crear otra con el mismo nombre'
                )
            );

        $response = [];

        $this->withJsonBody(
            json_encode([
                'nombre' => 'Entre Ríos'
            ]),
            function () use (&$response) {
                $response = $this->captureJson(
                    fn() => $this->controller->store()
                );
            }
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Provincia existente, no se puede crear otra con el mismo nombre',
            $response['error']
        );
    }

    // =========================================================
    // UPDATE
    // =========================================================

    public function test_update_actualiza_una_provincia_correctamente(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                ['nombre' => 'Córdoba']
            );

        $response = [];

        $this->withJsonBody(
            json_encode([
                'nombre' => 'Córdoba'
            ]),
            function () use (&$response) {
                $response = $this->captureJson(
                    fn() => $this->controller->update(1)
                );
            }
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Provincia actualizada exitosamente',
            $response['message']
        );
    }

    public function test_update_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('actualizar');

        $response = $this->captureJson(
            fn() => $this->controller->update(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token inválido o expirado',
            $response['error']
        );
    }

    public function test_update_requiere_rol_admin(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('actualizar');

        $response = [];

        $this->withJsonBody(
            json_encode([
                'nombre' => 'Córdoba'
            ]),
            function () use (&$response) {
                $response = $this->captureJson(
                    fn() => $this->controller->update(1)
                );
            }
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Solo administradores',
            $response['error']
        );
    }

    public function test_update_devuelve_not_found_si_no_existe(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                999,
                ['nombre' => 'Córdoba']
            )
            ->willThrowException(
                new NotFoundException(
                    'Provincia no encontrada'
                )
            );

        $response = [];

        $this->withJsonBody(
            json_encode([
                'nombre' => 'Córdoba'
            ]),
            function () use (&$response) {
                $response = $this->captureJson(
                    fn() => $this->controller->update(999)
                );
            }
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Provincia no encontrada',
            $response['error']
        );
    }

    public function test_update_devuelve_conflict_si_el_nombre_ya_existe(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                ['nombre' => 'Córdoba']
            )
            ->willThrowException(
                new ConflictException(
                    'El nombre ya está registrado'
                )
            );

        $response = [];

        $this->withJsonBody(
            json_encode([
                'nombre' => 'Córdoba'
            ]),
            function () use (&$response) {
                $response = $this->captureJson(
                    fn() => $this->controller->update(1)
                );
            }
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'El nombre ya está registrado',
            $response['error']
        );
    }

    public function test_update_devuelve_bad_request_si_no_hay_campos(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, [])
            ->willThrowException(
                new BadRequestException(
                    'Debe enviar al menos un campo para actualizar'
                )
            );

        $response = [];

        $this->withJsonBody(
            json_encode([]),
            function () use (&$response) {
                $response = $this->captureJson(
                    fn() => $this->controller->update(1)
                );
            }
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Debe enviar al menos un campo para actualizar',
            $response['error']
        );
    }

    // =========================================================
    // DELETE
    // =========================================================

    public function test_delete_elimina_una_provincia_correctamente(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1);

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Provincia eliminada exitosamente',
            $response['message']
        );
    }

    public function test_delete_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('eliminar');

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token inválido o expirado',
            $response['error']
        );
    }

    public function test_delete_requiere_rol_admin(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('eliminar');

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Solo administradores',
            $response['error']
        );
    }

    public function test_delete_devuelve_not_found_si_no_existe(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(999)
            ->willThrowException(
                new NotFoundException(
                    'Provincia no encontrada'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->delete(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Provincia no encontrada',
            $response['error']
        );
    }

    public function test_delete_devuelve_conflict_si_tiene_localidades(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1)
            ->willThrowException(
                new ConflictException(
                    'No se puede eliminar porque tiene localidades asociadas'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No se puede eliminar porque tiene localidades asociadas',
            $response['error']
        );
    }

    // =========================================================
    // RESTORE
    // =========================================================

    public function test_restore_restaura_una_provincia_correctamente(): void
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
            'Provincia restaurada exitosamente',
            $response['message']
        );
    }

    public function test_restore_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('restaurar');

        $response = $this->captureJson(
            fn() => $this->controller->restore(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token inválido o expirado',
            $response['error']
        );
    }

    public function test_restore_requiere_rol_admin(): void
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
                    'Provincia eliminada no encontrada'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->restore(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Provincia eliminada no encontrada',
            $response['error']
        );
    }

    public function test_restore_devuelve_conflict_si_el_nombre_ya_esta_activo(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1)
            ->willThrowException(
                new ConflictException(
                    'Ya existe una provincia activa con ese nombre'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->restore(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Ya existe una provincia activa con ese nombre',
            $response['error']
        );
    }
}