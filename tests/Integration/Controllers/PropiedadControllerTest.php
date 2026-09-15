<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\PropiedadController;
use App\Services\PropiedadService;
use App\Models\Propiedad;
use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use PHPUnit\Framework\MockObject\MockObject;

class PropiedadControllerTest extends TestCase
{
    private $controller;

    /** @var PropiedadService&MockObject */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        $this->service = $this->createMock(PropiedadService::class);
        $this->controller = new PropiedadController($this->service);
    }

    protected function tearDown(): void
    {
        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        parent::tearDown();
    }

    public function test_it_can_listar(): void
    {
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
            ->willReturn($data);

        $response = $this->captureJson(
            fn() => $this->controller->listar()
        );

        $this->assertTrue($response['success']);
        $this->assertSame($data, $response['data']);
    }

    public function test_it_can_mis_propiedades(): void
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

    public function test_it_returns_unauthorized_when_mis_propiedades_without_token(): void
    {
        $this->service
            ->expects($this->never())
            ->method('misPropiedades');

        $response = $this->captureJson(
            fn() => $this->controller->misPropiedades()
        );

        $this->assertFalse($response['success']);
        $this->assertSame('Token requerido', $response['error']);
    }

    public function test_it_can_obtener(): void
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
            fn() => $this->controller->obtener(1)
        );

        $this->assertTrue($response['success']);
        $this->assertSame(1, $response['data']['id']);
        $this->assertSame('Casa', $response['data']['titulo']);
    }

    public function test_it_returns_bad_request_when_obtener_id_is_invalid(): void
    {
        $this->service
            ->expects($this->never())
            ->method('obtener');

        $response = $this->captureJson(
            fn() => $this->controller->obtener('abc')
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'ID de propiedad inválido',
            $response['error']
        );
    }

    public function test_it_returns_not_found_when_obtener_fails(): void
    {
        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(999)
            ->willThrowException(
                new NotFoundException('Propiedad no encontrada')
            );

        $response = $this->captureJson(
            fn() => $this->controller->obtener(999)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Propiedad no encontrada',
            $response['error']
        );
    }

    public function test_it_can_crear(): void
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
            ->willReturn($propiedad);

        $response = [];

        $this->withJsonBody(
            json_encode($data),
            function () use (&$response): void {
                $response = $this->captureJson(
                    fn() => $this->controller->crear()
                );
            }
        );

        $this->assertTrue($response['success']);
        $this->assertSame(10, $response['data']['id']);
        $this->assertSame('Casa nueva', $response['data']['titulo']);
        $this->assertSame(
            'Propiedad creada exitosamente',
            $response['message']
        );
    }

    public function test_it_returns_unauthorized_when_crear_without_token(): void
    {
        $this->service
            ->expects($this->never())
            ->method('crear');

        $response = $this->captureJson(
            fn() => $this->controller->crear()
        );

        $this->assertFalse($response['success']);
        $this->assertSame('Token requerido', $response['error']);
    }

    public function test_it_returns_validation_error_when_crear_fails_validation(): void
    {
        $this->actingAs(5, 1);

        $data = [];

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with($data, 5)
            ->willThrowException(
                new ValidationException([
                    'titulo' => ['El título es obligatorio'],
                ])
            );
            
        $response = [];

        $this->withJsonBody(
            json_encode($data),
            function () use (&$response): void {
                $response = $this->captureJson(
                    fn() => $this->controller->crear()
                );
            }
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

    public function test_it_can_actualizar(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => 'Casa actualizada',
            'precio' => 150000,
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(5, 1, 10, $data);

        $response = [];

        $this->withJsonBody(
            json_encode($data),
            function () use (&$response): void {
                $response = $this->captureJson(
                    fn() => $this->controller->actualizar(10)
                );
            }
        );

        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']);
        $this->assertSame(
            'Propiedad actualizada exitosamente',
            $response['message']
        );
    }

    public function test_it_returns_bad_request_when_actualizar_id_is_invalid(): void
    {
        $this->service
            ->expects($this->never())
            ->method('actualizar');

        $response = $this->captureJson(
            fn() => $this->controller->actualizar('abc')
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'ID de propiedad inválido',
            $response['error']
        );
    }

    public function test_it_returns_unauthorized_when_actualizar_without_token(): void
    {
        $this->service
            ->expects($this->never())
            ->method('actualizar');

        $response = [];

        $this->withJsonBody(
            json_encode(['titulo' => 'Actualizada']),
            function () use (&$response): void {
                $response = $this->captureJson(
                    fn() => $this->controller->actualizar(10)
                );
            }
        );

        $this->assertFalse($response['success']);
        $this->assertSame('Token requerido', $response['error']);
    }

    public function test_it_returns_not_found_when_actualizar_fails(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => 'Casa actualizada',
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(5, 1, 10, $data)
            ->willThrowException(
                new NotFoundException('Propiedad no encontrada')
            );

        $response = [];

        $this->withJsonBody(
            json_encode($data),
            function () use (&$response): void {
                $response = $this->captureJson(
                    fn() => $this->controller->actualizar(10)
                );
            }
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Propiedad no encontrada',
            $response['error']
        );
    }

    public function test_it_returns_forbidden_when_actualizar_is_not_allowed(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => 'Casa actualizada',
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(5, 1, 10, $data)
            ->willThrowException(
                new ForbiddenException(
                    'No tienes permiso para gestionar esta propiedad'
                )
            );

        $response = [];

        $this->withJsonBody(
            json_encode($data),
            function () use (&$response): void {
                $response = $this->captureJson(
                    fn() => $this->controller->actualizar(10)
                );
            }
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No tienes permiso para gestionar esta propiedad',
            $response['error']
        );
    }

    public function test_it_returns_validation_error_when_actualizar_fails_validation(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => '',
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(5, 1, 10, $data)
            ->willThrowException(
                new ValidationException([
                    'titulo' => ['El título es obligatorio'],
                ])
            );

        $response = [];

        $this->withJsonBody(
            json_encode($data),
            function () use (&$response): void {
                $response = $this->captureJson(
                    fn() => $this->controller->actualizar(10)
                );
            }
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

    public function test_it_returns_bad_request_when_actualizar_fails_bad_request(): void
    {
        $this->actingAs(5, 1);

        $data = [
            'titulo' => 'Casa',
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(5, 1, 10, $data)
            ->willThrowException(
                new BadRequestException(
                    'No se enviaron campos actualizables'
                )
            );

        $response = [];

        $this->withJsonBody(
            json_encode($data),
            function () use (&$response): void {
                $response = $this->captureJson(
                    fn() => $this->controller->actualizar(10)
                );
            }
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No se enviaron campos actualizables',
            $response['error']
        );
    }

    public function test_it_can_eliminar(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(5, 1, 10);

        $response = $this->captureJson(
            fn() => $this->controller->eliminar(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']);
        $this->assertSame(
            'Propiedad eliminada exitosamente',
            $response['message']
        );
    }

    public function test_it_returns_bad_request_when_eliminar_id_is_invalid(): void
    {
        $this->service
            ->expects($this->never())
            ->method('eliminar');

        $response = $this->captureJson(
            fn() => $this->controller->eliminar('abc')
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'ID de propiedad inválido',
            $response['error']
        );
    }

    public function test_it_returns_unauthorized_when_eliminar_without_token(): void
    {
        $this->service
            ->expects($this->never())
            ->method('eliminar');

        $response = $this->captureJson(
            fn() => $this->controller->eliminar(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame('Token requerido', $response['error']);
    }

    public function test_it_returns_not_found_when_eliminar_fails(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(5, 1, 10)
            ->willThrowException(
                new NotFoundException('Propiedad no encontrada')
            );

        $response = $this->captureJson(
            fn() => $this->controller->eliminar(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Propiedad no encontrada',
            $response['error']
        );
    }

    public function test_it_returns_forbidden_when_eliminar_is_not_allowed(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(5, 1, 10)
            ->willThrowException(
                new ForbiddenException(
                    'No tienes permiso para gestionar esta propiedad'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->eliminar(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No tienes permiso para gestionar esta propiedad',
            $response['error']
        );
    }

    public function test_it_can_restaurar(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(5, 1, 10);

        $response = $this->captureJson(
            fn() => $this->controller->restaurar(10)
        );

        $this->assertTrue($response['success']);
        $this->assertSame([], $response['data']);
        $this->assertSame(
            'Propiedad restaurada exitosamente',
            $response['message']
        );
    }

    public function test_it_returns_bad_request_when_restaurar_id_is_invalid(): void
    {
        $this->service
            ->expects($this->never())
            ->method('restaurar');

        $response = $this->captureJson(
            fn() => $this->controller->restaurar('abc')
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'ID de propiedad inválido',
            $response['error']
        );
    }

    public function test_it_returns_unauthorized_when_restaurar_without_token(): void
    {
        $this->service
            ->expects($this->never())
            ->method('restaurar');

        $response = $this->captureJson(
            fn() => $this->controller->restaurar(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame('Token requerido', $response['error']);
    }

    public function test_it_returns_not_found_when_restaurar_fails(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(5, 1, 10)
            ->willThrowException(
                new NotFoundException('Propiedad no encontrada')
            );

        $response = $this->captureJson(
            fn() => $this->controller->restaurar(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'Propiedad no encontrada',
            $response['error']
        );
    }

    public function test_it_returns_forbidden_when_restaurar_is_not_allowed(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(5, 1, 10)
            ->willThrowException(
                new ForbiddenException(
                    'No tienes permiso para gestionar esta propiedad'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->restaurar(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'No tienes permiso para gestionar esta propiedad',
            $response['error']
        );
    }

    public function test_it_returns_bad_request_when_restaurar_fails(): void
    {
        $this->actingAs(5, 1);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(5, 1, 10)
            ->willThrowException(
                new BadRequestException(
                    'La propiedad no está eliminada'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->restaurar(10)
        );

        $this->assertFalse($response['success']);
        $this->assertSame(
            'La propiedad no está eliminada',
            $response['error']
        );
    }
}