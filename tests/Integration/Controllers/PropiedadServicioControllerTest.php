<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\PropiedadServicioController;
use App\Services\PropiedadServicioService;
use App\Helpers\Request;
use App\Helpers\TokenProviderInterface;
use App\Middlewares\AutenticadorMiddleware;

class PropiedadServicioControllerTest extends TestCase
{
    private $controller;
    private $service;
    private $tokenProviderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(
            PropiedadServicioService::class
        );

        $this->controller = new PropiedadServicioController(
            $this->service
        );

        $this->tokenProviderMock = $this->createMock(
            TokenProviderInterface::class
        );

        $this->tokenProviderMock
            ->method('validate')
            ->willReturn(
                (object) [
                    'sub' => 1,
                    'rol_id' => 1
                ]
            );

        AutenticadorMiddleware::configure(
            $this->tokenProviderMock
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_usuario';
    }

    protected function tearDown(): void
    {
        Request::setTestBody(null);

        unset(
            $_SERVER['HTTP_AUTHORIZATION']
        );

        parent::tearDown();
    }

    public function test_el_controlador_puede_listar_servicios_de_una_propiedad(): void
    {
        $servicios = [
            [
                'propiedad_id' => 1,
                'servicio_id' => 2
            ],
            [
                'propiedad_id' => 1,
                'servicio_id' => 3
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(1)
            ->willReturn($servicios);

        $response = $this->captureJson(
            fn() => $this->controller->index(null, 1)
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            $servicios,
            $response['data']['items']
        );

        $this->assertSame(
            2,
            $response['data']['total']
        );
    }

    public function test_el_controlador_puede_listar_propiedades_por_servicio(): void
    {
        $propiedades = [
            [
                'propiedad_id' => 1,
                'servicio_id' => 2
            ],
            [
                'propiedad_id' => 3,
                'servicio_id' => 2
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('listarPorServicio')
            ->with(2)
            ->willReturn($propiedades);

        $response = $this->captureJson(
            fn() => $this->controller
                ->getPropiedadesByServicio(null, 2)
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            $propiedades,
            $response['data']['items']
        );

        $this->assertSame(
            2,
            $response['data']['total']
        );
    }

    public function test_el_controlador_puede_asignar_un_servicio(): void
    {
        $this->actingAs(1, 1);

        Request::setTestBody(
            json_encode([
                'servicio_id' => 2
            ])
        );

        $this->service
            ->expects($this->once())
            ->method('asignar')
            ->with(
                1,
                2,
                1,
                1
            )
            ->willReturn([
                'propiedad_id' => 1,
                'servicio_id' => 2
            ]);

        $response = $this->captureJson(
            fn() => $this->controller->store(null, 1)
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            'Servicio asignado correctamente',
            $response['message']
        );

        $this->assertSame(
            201,
            http_response_code()
        );
    }

    public function test_el_controlador_verifica_autenticacion_al_asignar(): void
    {
        $this->tokenProviderMock
            ->expects($this->once())
            ->method('validate')
            ->with('token_usuario')
            ->willReturn(
                (object) [
                    'sub' => 1,
                    'rol_id' => 1
                ]
            );

        Request::setTestBody(
            json_encode([
                'servicio_id' => 2
            ])
        );

        $this->service
            ->expects($this->once())
            ->method('asignar')
            ->with(
                1,
                2,
                1,
                1
            )
            ->willReturn([
                'propiedad_id' => 1,
                'servicio_id' => 2
            ]);

        $response = $this->captureJson(
            fn() => $this->controller->store(null, 1)
        );

        $this->assertTrue(
            $response['success']
        );
    }

    public function test_el_controlador_puede_asignar_multiples_servicios(): void
    {
        $this->actingAs(1, 1);

        Request::setTestBody(
            json_encode([
                'servicio_ids' => [1, 2, 3]
            ])
        );

        $resultados = [
            'asignados' => [1, 2],
            'duplicados' => [3],
            'errores' => []
        ];

        $this->service
            ->expects($this->once())
            ->method('asignarMultiples')
            ->with(
                1,
                [1, 2, 3],
                1,
                1
            )
            ->willReturn($resultados);

        $response = $this->captureJson(
            fn() => $this->controller->storeMultiple(null, 1)
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            $resultados,
            $response['data']
        );

        $this->assertSame(
            'Servicios asignados correctamente',
            $response['message']
        );
    }

    public function test_el_controlador_puede_sincronizar_servicios(): void
    {
        $this->actingAs(1, 1);

        Request::setTestBody(
            json_encode([
                'servicio_ids' => [1, 3]
            ])
        );

        $resultados = [
            'agregados' => [3],
            'eliminados' => [2],
            'mantenidos' => [1]
        ];

        $this->service
            ->expects($this->once())
            ->method('sincronizar')
            ->with(
                1,
                [1, 3],
                1,
                1
            )
            ->willReturn($resultados);

        $response = $this->captureJson(
            fn() => $this->controller->update(null, 1)
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            $resultados,
            $response['data']
        );

        $this->assertSame(
            'Servicios sincronizados correctamente',
            $response['message']
        );
    }

    public function test_el_controlador_puede_desasignar_un_servicio(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->once())
            ->method('desasignar')
            ->with(
                1,
                2,
                1,
                1
            )
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->delete(
                null,
                1,
                2
            )
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            'Servicio desasignado correctamente',
            $response['message']
        );
    }

    public function test_el_controlador_maneja_error_al_listar_propiedad_inexistente(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(999)
            ->willThrowException(
                new \App\Exceptions\NotFoundException(
                    'La propiedad no existe'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->index(null, 999)
        );

        $this->assertFalse(
            $response['success']
        );

        $this->assertSame(
            'La propiedad no existe',
            $response['error']
        );
    }

    public function test_el_controlador_maneja_error_al_listar_servicio_inexistente(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listarPorServicio')
            ->with(999)
            ->willThrowException(
                new \App\Exceptions\NotFoundException(
                    'El servicio no existe'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller
                ->getPropiedadesByServicio(null, 999)
        );

        $this->assertFalse(
            $response['success']
        );

        $this->assertSame(
            'El servicio no existe',
            $response['error']
        );
    }

    public function test_el_controlador_maneja_error_al_asignar_servicio(): void
    {
        $this->actingAs(1, 1);

        Request::setTestBody(
            json_encode([
                'servicio_id' => 2
            ])
        );

        $this->service
            ->expects($this->once())
            ->method('asignar')
            ->with(
                1,
                2,
                1,
                1
            )
            ->willThrowException(
                new \App\Exceptions\ConflictException(
                    'La propiedad ya tiene este servicio asignado'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->store(null, 1)
        );

        $this->assertFalse(
            $response['success']
        );

        $this->assertSame(
            'La propiedad ya tiene este servicio asignado',
            $response['error']
        );
    }

    public function test_el_controlador_maneja_error_al_desasignar_servicio(): void
    {
        $this->actingAs(1, 1);

        $this->service
            ->expects($this->once())
            ->method('desasignar')
            ->with(
                1,
                999,
                1,
                1
            )
            ->willThrowException(
                new \App\Exceptions\NotFoundException(
                    'La propiedad no tiene este servicio asignado'
                )
            );

        $response = $this->captureJson(
            fn() => $this->controller->delete(
                null,
                1,
                999
            )
        );

        $this->assertFalse(
            $response['success']
        );

        $this->assertSame(
            'La propiedad no tiene este servicio asignado',
            $response['error']
        );
    }
}