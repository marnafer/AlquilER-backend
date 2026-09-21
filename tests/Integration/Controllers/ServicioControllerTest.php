<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\ServicioController;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Servicio;
use App\Services\ServicioService;
use Tests\TestCase;

final class ServicioControllerTest extends TestCase
{
    private $service;
    private ServicioController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(
            ServicioService::class
        );

        $this->controller = new ServicioController(
            $this->service
        );
    }

    public function test_index_devuelve_los_servicios(): void
    {
        $resultado = [
            'items' => [],
            'total' => 0,
        ];

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

    public function test_show_devuelve_un_servicio(): void
    {
        $servicio = new Servicio([
            'nombre' => 'WiFi',
        ]);

        $servicio->id = 1;

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($servicio);

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'WiFi',
            $response['data']['nombre']
        );
    }

    public function test_show_devuelve_not_found_si_no_existe(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(
                new NotFoundException(
                    'Servicio no encontrado'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->show(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Servicio no encontrado',
            $response['error']
        );
    }

    public function test_store_crea_un_servicio(): void
    {
        $servicio = new Servicio([
            'nombre' => 'WiFi',
        ]);

        $servicio->id = 1;

        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([
                'nombre' => 'Nuevo Servicio',
            ])
            ->willReturn($servicio);

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Nuevo Servicio',
            ]),
            fn() => $this->controller->store()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Servicio creado exitosamente',
            $response['message']
        );
        $this->assertSame(
            'WiFi',
            $response['data']['nombre']
        );

        $this->assertSame(
            1,
            $response['data']['id']
        );
    }

    public function test_store_requiere_ser_admin(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('crear');

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Nuevo Servicio',
            ]),
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Solo administradores',
            $response['error']
        );
    }

    public function test_store_devuelve_error_de_validacion(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([])
            ->willThrowException(
                new ValidationException([
                    'nombre' => [
                        'El nombre del servicio es requerido'
                    ]
                ])
            );

        $response = $this->captureJsonWithBody(
            json_encode([]),
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'El nombre del servicio es requerido',
            $response['validation_errors']['nombre'][0]
        );
    }

    public function test_update_actualiza_un_servicio(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                [
                    'nombre' => 'Servicio Actualizado'
                ]
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Servicio Actualizado',
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Servicio actualizado exitosamente',
            $response['message']
        );
    }

    public function test_update_requiere_ser_admin(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->never())
            ->method('actualizar');

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Actualizado',
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Solo administradores',
            $response['error']
        );
    }

    public function test_update_devuelve_error_de_conflicto(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                [
                    'nombre' => 'WiFi'
                ]
            )
            ->willThrowException(
                new ConflictException(
                    'Ya existe un servicio con ese nombre'
                )
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'WiFi',
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Ya existe un servicio con ese nombre',
            $response['error']
        );
    }

    public function test_delete_elimina_un_servicio(): void
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
            'Servicio eliminado exitosamente',
            $response['message']
        );
    }

    public function test_delete_requiere_ser_admin(): void
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

    public function test_delete_devuelve_error_de_conflicto(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1)
            ->willThrowException(
                new ConflictException(
                    'No se puede eliminar el servicio porque tiene propiedades asociadas'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No se puede eliminar el servicio porque tiene propiedades asociadas',
            $response['error']
        );
    }

    public function test_restore_restaura_un_servicio(): void
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
            'Servicio restaurado exitosamente',
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
                    'Servicio no encontrado o no eliminado'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->restore(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Servicio no encontrado o no eliminado',
            $response['error']
        );
    }
}