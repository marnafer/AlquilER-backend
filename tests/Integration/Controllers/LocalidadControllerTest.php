<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\LocalidadController;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Localidad;
use App\Services\LocalidadService;
use Tests\TestCase;

final class LocalidadControllerTest extends TestCase
{
    private $service;
    private LocalidadController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        $this->service = $this->createMock(
            LocalidadService::class
        );

        $this->controller = new LocalidadController(
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

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function test_index_devuelve_las_localidades(): void
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

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function test_show_devuelve_una_localidad(): void
    {
        $localidad = new Localidad([
            'nombre' => 'Crespo',
            'codigo_postal' => '3116',
            'provincia_id' => 1,
        ]);

        $localidad->id = 1;

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($localidad);

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Crespo',
            $response['data']['nombre']
        );
        $this->assertSame(
            1,
            $response['data']['id']
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
                    'Localidad no encontrada'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->show(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Localidad no encontrada',
            $response['error']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function test_store_crea_una_localidad(): void
    {
        $localidad = new Localidad([
            'nombre' => 'Crespo',
            'codigo_postal' => '3116',
            'provincia_id' => 1,
        ]);

        $localidad->id = 1;

        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([
                'nombre' => 'Crespo',
                'codigo_postal' => '3116',
                'provincia_id' => 1,
            ])
            ->willReturn($localidad);

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Crespo',
                'codigo_postal' => '3116',
                'provincia_id' => 1,
            ]),
            fn() => $this->controller->store()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Localidad creada exitosamente',
            $response['message']
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
                'nombre' => 'Crespo',
                'codigo_postal' => '3116',
                'provincia_id' => 1,
            ]),
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Solo administradores',
            $response['error']
        );
    }

    public function test_store_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('crear');

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Crespo',
                'codigo_postal' => '3116',
                'provincia_id' => 1,
            ]),
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_store_devuelve_error_de_validacion(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([
                'nombre' => 'A',
                'codigo_postal' => '3116',
                'provincia_id' => 1,
            ])
            ->willThrowException(
                new ValidationException([
                    'nombre' => [
                        'El nombre debe tener al menos 2 caracteres'
                    ],
                ])
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'A',
                'codigo_postal' => '3116',
                'provincia_id' => 1,
            ]),
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'El nombre debe tener al menos 2 caracteres',
            $response['validation_errors']['nombre'][0]
        );
    }

    public function test_store_devuelve_error_de_conflicto(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([
                'nombre' => 'Crespo',
                'codigo_postal' => '3116',
                'provincia_id' => 1,
            ])
            ->willThrowException(
                new ConflictException(
                    'Ya existe una localidad con ese nombre en la provincia seleccionada'
                )
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Crespo',
                'codigo_postal' => '3116',
                'provincia_id' => 1,
            ]),
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Ya existe una localidad con ese nombre en la provincia seleccionada',
            $response['error']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function test_update_actualiza_una_localidad(): void
    {
        $this->actingAs(1, 2);

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                [
                    'nombre' => 'Crespo Actualizado',
                ]
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Crespo Actualizado',
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Localidad actualizada exitosamente',
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
                'nombre' => 'Crespo Actualizado',
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Solo administradores',
            $response['error']
        );
    }

    public function test_update_requiere_autenticacion(): void
    {
        $this->service
            ->expects($this->never())
            ->method('actualizar');

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Crespo Actualizado',
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_update_devuelve_error_de_validacion(): void
    {
        $this->actingAs(1, 2);

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
                        'El nombre debe tener al menos 2 caracteres'
                    ],
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
            'El nombre debe tener al menos 2 caracteres',
            $response['validation_errors']['nombre'][0]
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
                    'nombre' => 'Crespo',
                ]
            )
            ->willThrowException(
                new ConflictException(
                    'Ya existe otra localidad con ese nombre en la provincia'
                )
            );

        $response = $this->captureJsonWithBody(
            json_encode([
                'nombre' => 'Crespo',
            ]),
            fn() => $this->controller->update(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Ya existe otra localidad con ese nombre en la provincia',
            $response['error']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function test_delete_elimina_una_localidad(): void
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
            'Localidad eliminada exitosamente',
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
            'Token requerido',
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
                    'Localidad no encontrada'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->delete(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Localidad no encontrada',
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
                    'No se puede eliminar porque tiene propiedades asociadas'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No se puede eliminar porque tiene propiedades asociadas',
            $response['error']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESTORE
    |--------------------------------------------------------------------------
    */

    public function test_restore_restaura_una_localidad(): void
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
            'Localidad restaurada exitosamente',
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
            'Token requerido',
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
                    'Localidad eliminada no encontrada'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->restore(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Localidad eliminada no encontrada',
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
                    'Ya existe una localidad activa con ese nombre en la provincia'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->restore(1)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Ya existe una localidad activa con ese nombre en la provincia',
            $response['error']
        );
    }
}