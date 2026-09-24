<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\PropiedadController;
use App\Services\PropiedadService;
use App\Models\Propiedad;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use PHPUnit\Framework\MockObject\MockObject;

class PropiedadControllerTest extends TestCase
{
    private PropiedadController $controller;

    /** @var PropiedadService&MockObject */
    private PropiedadService $service;

    protected function setUp(): void
    {
        parent::setUp();

        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        $this->service = $this->createMock(
            PropiedadService::class
        );

        $this->controller = new PropiedadController(
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

        public function test_it_can_list_properties(): void
    {
        $_GET = [];

        $data = [
            'items' => [
                ['id' => 1, 'titulo' => 'Casa 1'],
                ['id' => 2, 'titulo' => 'Casa 2'],
            ],
            'total' => 2,
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn($data);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);
        $this->assertSame($data, $response['data']);

        $_GET = [];
    }

    public function test_adminIndex_devuelve_el_listado_global_de_propiedades(): void
    {
        $this->actingAs(5, 2);

        $_GET = [];

        $propiedades = [
            'items' => [
                ['id' => 1, 'titulo' => 'Casa 1'],
            ],
            'total' => 1,
        ];

        $this->service
            ->expects($this->once())
            ->method('listarParaAdmin')
            ->with([])
            ->willReturn($propiedades);

        $response = $this->captureJson(
            fn() => $this->controller->adminIndex()
        );

        $this->assertTrue($response['success']);
        $this->assertSame($propiedades, $response['data']);
    }

    public function test_adminIndex_pasa_los_filtros_al_service(): void
    {
        $this->actingAs(5, 2);

        $_GET = ['solo_eliminados' => 'true'];

        $propiedades = [
            'items' => [],
            'total' => 0,
        ];

        $this->service
            ->expects($this->once())
            ->method('listarParaAdmin')
            ->with($_GET)
            ->willReturn($propiedades);

        $response = $this->captureJson(
            fn() => $this->controller->adminIndex()
        );

        $this->assertTrue($response['success']);
        $this->assertSame($propiedades, $response['data']);

        $_GET = [];
    }

    public function test_adminIndex_rechaza_a_usuario_no_admin(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->never())
            ->method('listarParaAdmin');

        $this->expectException(ForbiddenException::class);

        $this->controller->adminIndex();
    }

    public function test_it_can_list_my_properties(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'items' => [
                ['id' => 1, 'titulo' => 'Mi casa'],
            ],
            'total' => 1,
        ];

        $this->service
            ->expects($this->once())
            ->method('misPropiedades')
            ->with(5)
            ->willReturn($data);

        $response = $this->captureJson(
            fn() => $this->controller->misPropiedades()
        );

        $this->assertTrue($response['success']);
        $this->assertSame($data, $response['data']);
    }

    public function test_it_returns_unauthorized_when_listing_my_properties_without_token(): void
    {
        $this->service
            ->expects($this->never())
            ->method('misPropiedades');

        $response = $this->captureJson(
            fn() => $this->controller->misPropiedades()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_it_can_show_a_property(): void
    {
        $propiedad = new Propiedad();

        $propiedad->setRawAttributes([
            'id' => 1,
            'titulo' => 'Casa',
        ]);

        $propiedad->setAppends([]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($propiedad);

        $response = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            1,
            $response['data']['id']
        );
        $this->assertSame(
            'Casa',
            $response['data']['titulo']
        );
    }

    public function test_it_returns_not_found_when_show_fails(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(
                new NotFoundException(
                    'Propiedad no encontrada'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->show(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Propiedad no encontrada',
            $response['error']
        );
    }

    public function test_it_returns_validation_error_when_show_id_is_invalid(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with('abc')
            ->willThrowException(
                new ValidationException([
                    'id' => [
                        'El ID de propiedad debe ser numérico'
                    ],
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

        $this->assertArrayHasKey(
            'validation_errors',
            $response
        );
    }

    public function test_it_can_create_a_property(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => 'Casa nueva',
            'descripcion' => 'Descripción',
            'precio' => 100000,
            'expensas' => 10000,
            'direccion' => 'Calle 123',
            'cantidad_ambientes' => 3,
            'cantidad_dormitorios' => 2,
            'cantidad_banos' => 1,
            'capacidad' => 4,
            'disponible' => 1,
            'categoria_id' => 1,
            'localidad_id' => 1,
        ];

        $propiedad = new Propiedad();

        $propiedad->setRawAttributes([
            'id' => 10,
            'titulo' => 'Casa nueva',
        ]);

        $propiedad->setAppends([]);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with($data, 5)
            ->willReturn($propiedad);

        $response = $this->captureJsonWithBody(
            json_encode($data),
            fn() => $this->controller->store()
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            10,
            $response['data']['id']
        );
        $this->assertSame(
            'Casa nueva',
            $response['data']['titulo']
        );
        $this->assertSame(
            'Propiedad creada exitosamente',
            $response['message']
        );
    }

    public function test_it_returns_unauthorized_when_creating_without_token(): void
    {
        $this->service
            ->expects($this->never())
            ->method('crear');

        $response = $this->captureJsonWithBody(
            json_encode([
                'titulo' => 'Casa nueva',
            ]),
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_it_returns_validation_error_when_creating_fails_validation(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => '',
        ];

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with($data, 5)
            ->willThrowException(
                new ValidationException([
                    'titulo' => [
                        'El título es obligatorio'
                    ],
                ])
            );

        $response = $this->captureJsonWithBody(
            json_encode($data),
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Error de validación',
            $response['error']
        );

        $this->assertArrayHasKey(
            'validation_errors',
            $response
        );

        $this->assertSame(
            ['El título es obligatorio'],
            $response['validation_errors']['titulo']
        );
    }

    public function test_it_returns_bad_request_when_creating_without_body(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->never())
            ->method('crear');

        $response = $this->captureJson(
            fn() => $this->controller->store()
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'El cuerpo de la solicitud es obligatorio',
            $response['error']
        );
    }

    public function test_it_can_update_a_property(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => 'Casa actualizada',
            'precio' => 150000,
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                5,
                1,
                10,
                $data
            );

        $response = $this->captureJsonWithBody(
            json_encode($data),
            fn() => $this->controller->update(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            [],
            $response['data']
        );
        $this->assertSame(
            'Propiedad actualizada exitosamente',
            $response['message']
        );
    }

    public function test_it_returns_unauthorized_when_updating_without_token(): void
    {
        $this->service
            ->expects($this->never())
            ->method('actualizar');

        $response = $this->captureJsonWithBody(
            json_encode([
                'titulo' => 'Actualizada',
            ]),
            fn() => $this->controller->update(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_it_returns_not_found_when_update_fails(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => 'Casa actualizada',
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                5,
                1,
                10,
                $data
            )
            ->willThrowException(
                new NotFoundException(
                    'Propiedad no encontrada'
                )
            );

        $response = $this->captureJsonWithBody(
            json_encode($data),
            fn() => $this->controller->update(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Propiedad no encontrada',
            $response['error']
        );
    }

    public function test_it_returns_forbidden_when_update_is_not_allowed(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => 'Casa actualizada',
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                5,
                1,
                10,
                $data
            )
            ->willThrowException(
                new ForbiddenException(
                    'No tienes permiso para gestionar esta propiedad'
                )
            );

        $response = $this->captureJsonWithBody(
            json_encode($data),
            fn() => $this->controller->update(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No tienes permiso para gestionar esta propiedad',
            $response['error']
        );
    }

    public function test_it_returns_validation_error_when_update_fails_validation(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => '',
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                5,
                1,
                10,
                $data
            )
            ->willThrowException(
                new ValidationException([
                    'titulo' => [
                        'El título es obligatorio'
                    ],
                ])
            );

        $response = $this->captureJsonWithBody(
            json_encode($data),
            fn() => $this->controller->update(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Error de validación',
            $response['error']
        );

        $this->assertArrayHasKey(
            'validation_errors',
            $response
        );
    }

    public function test_it_returns_bad_request_when_update_fails_bad_request(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => 'Casa',
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                5,
                1,
                10,
                $data
            )
            ->willThrowException(
                new BadRequestException(
                    'No se enviaron campos actualizables'
                )
            );

        $response = $this->captureJsonWithBody(
            json_encode($data),
            fn() => $this->controller->update(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No se enviaron campos actualizables',
            $response['error']
        );
    }

    public function test_it_returns_bad_request_when_update_body_is_empty(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->never())
            ->method('actualizar');

        $response = $this->captureJson(
            fn() => $this->controller->update(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'El cuerpo de la solicitud es obligatorio',
            $response['error']
        );
    }

    public function test_it_can_delete_a_property(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(
                5,
                1,
                10
            );

        $response = $this->captureJson(
            fn() => $this->controller->delete(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            [],
            $response['data']
        );
        $this->assertSame(
            'Propiedad eliminada exitosamente',
            $response['message']
        );
    }

    public function test_it_returns_unauthorized_when_deleting_without_token(): void
    {
        $this->service
            ->expects($this->never())
            ->method('eliminar');

        $response = $this->captureJson(
            fn() => $this->controller->delete(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_it_returns_not_found_when_delete_fails(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(
                5,
                1,
                10
            )
            ->willThrowException(
                new NotFoundException(
                    'Propiedad no encontrada'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->delete(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Propiedad no encontrada',
            $response['error']
        );
    }

    public function test_it_returns_forbidden_when_delete_is_not_allowed(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(
                5,
                1,
                10
            )
            ->willThrowException(
                new ForbiddenException(
                    'No tienes permiso para gestionar esta propiedad'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->delete(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No tienes permiso para gestionar esta propiedad',
            $response['error']
        );
    }

    public function test_it_returns_conflict_when_delete_has_active_reservation(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(
                5,
                1,
                10
            )
            ->willThrowException(
                new ConflictException(
                    'No se puede eliminar la propiedad porque tiene una reserva activa'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->delete(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No se puede eliminar la propiedad porque tiene una reserva activa',
            $response['error']
        );
    }

    public function test_it_can_restore_a_property(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(
                5,
                1,
                10
            );

        $response = $this->captureJson(
            fn() => $this->controller->restore(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(
            [],
            $response['data']
        );
        $this->assertSame(
            'Propiedad restaurada exitosamente',
            $response['message']
        );
    }

    public function test_it_returns_unauthorized_when_restoring_without_token(): void
    {
        $this->service
            ->expects($this->never())
            ->method('restaurar');

        $response = $this->captureJson(
            fn() => $this->controller->restore(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Token requerido',
            $response['error']
        );
    }

    public function test_it_returns_not_found_when_restore_fails(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(
                5,
                1,
                10
            )
            ->willThrowException(
                new NotFoundException(
                    'Propiedad no encontrada'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->restore(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Propiedad no encontrada',
            $response['error']
        );
    }

    public function test_it_returns_forbidden_when_restore_is_not_allowed(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(
                5,
                1,
                10
            )
            ->willThrowException(
                new ForbiddenException(
                    'No tienes permiso para gestionar esta propiedad'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->restore(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No tienes permiso para gestionar esta propiedad',
            $response['error']
        );
    }

    public function test_it_returns_validation_error_when_restore_id_is_invalid(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(
                5,
                1,
                'abc'
            )
            ->willThrowException(
                new ValidationException([
                    'id' => [
                        'El ID de propiedad debe ser numérico'
                    ],
                ])
            );

        $response = $this->captureJson(
            fn() => $this->controller->restore('abc')
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Error de validación',
            $response['error']
        );

        $this->assertArrayHasKey(
            'validation_errors',
            $response
        );
    }

    public function test_index_pasa_los_filtros_al_service(): void
    {
        $_GET = [
            'categoria_id' => ['1', '2'],
            'localidad_id' => ['1', '3'],
        ];

        $data = [
            'items' => [
                ['id' => 1, 'titulo' => 'Casa filtrada'],
            ],
            'total' => 1,
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with($_GET)
            ->willReturn($data);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);
        $this->assertSame($data, $response['data']);

        $_GET = [];
    }
}