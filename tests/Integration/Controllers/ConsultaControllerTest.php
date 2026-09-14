<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\ConsultaController;
use App\Services\ConsultaService;
use App\Models\Consulta;
use App\Middlewares\AutenticadorMiddleware;
use App\Helpers\TokenProviderInterface;
use App\Helpers\Request;

class ConsultaControllerTest extends TestCase
{
    private $controller;
    private $service;
    private $tokenProviderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(ConsultaService::class);
        $this->controller = new ConsultaController($this->service);

        $this->tokenProviderMock = $this->createMock(
            TokenProviderInterface::class
        );

        $this->tokenProviderMock
            ->method('validate')
            ->willReturnCallback(function ($token) {
                if ($token === 'admin-token') {
                    return (object) [
                        'sub' => 99,
                        'rol_id' => 2
                    ];
                }

                return (object) [
                    'sub' => 1,
                    'rol_id' => 1
                ];
            });

        AutenticadorMiddleware::configure($this->tokenProviderMock);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer fake-jwt-token-for-testing';
        $_GET = [];
    }

    protected function tearDown(): void
    {
        Request::setTestBody(null);

        unset($_SERVER['HTTP_AUTHORIZATION']);

        $_GET = [];

        parent::tearDown();
    }

    public function test_el_controlador_permite_al_admin_listar_todas_las_consultas(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer admin-token';

        $consultas = [
            [
                'id' => 1,
                'propiedad_id' => 10
            ],
            [
                'id' => 2,
                'propiedad_id' => 20
            ]
        ];

        $_GET = [
            'usuario_id' => '5'
        ];

        $this->service
            ->expects($this->once())
            ->method('listarConsultas')
            ->with(
                2,
                ['usuario_id' => '5']
            )
            ->willReturn($consultas);

        ob_start();

        try {
            $this->controller->adminIndex();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode($output, true);

        $this->assertTrue($response['success']);
        $this->assertSame($consultas, $response['data']['items']);
        $this->assertSame(2, $response['data']['total']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_listar_las_consultas_del_usuario(): void
    {
        $consultas = [
            [
                'id' => 1,
                'propiedad_id' => 10
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('obtenerConsultasPorUsuario')
            ->with(1, 1, 1)
            ->willReturn($consultas);

        ob_start();

        try {
            $this->controller->index();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode($output, true);

        $this->assertTrue($response['success']);
        $this->assertSame($consultas, $response['data']['items']);
        $this->assertSame(1, $response['data']['total']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_mostrar_una_consulta_especifica(): void
    {
        $consulta = new Consulta([
            'id' => 1
        ]);

        $this->service
            ->expects($this->once())
            ->method('obtenerConsultaAutorizada')
            ->with(1, 1)
            ->willReturn($consulta);

        ob_start();

        try {
            $this->controller->show(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode($output, true);

        $this->assertTrue($response['success']);
        $this->assertSame(200, http_response_code());
        $this->assertArrayHasKey('data', $response);
    }

    public function test_el_controlador_puede_crear_una_consulta(): void
    {
        Request::setTestBody(json_encode([
            'propiedad_id' => 1,
            'mensaje' => 'Interesado en la propiedad'
        ]));

        $this->service
            ->expects($this->once())
            ->method('crearConsulta')
            ->with([
                'propiedad_id' => 1,
                'mensaje' => 'Interesado en la propiedad',
                'usuario_id' => 1
            ])
            ->willReturn(25);

        ob_start();

        try {
            $this->controller->store();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode($output, true);

        $this->assertTrue($response['success']);
        $this->assertSame(25, $response['data']['id']);
        $this->assertSame(
            'Consulta creada exitosamente',
            $response['message']
        );
        $this->assertSame(201, http_response_code());
    }

    public function test_el_controlador_puede_actualizar_una_consulta(): void
    {
        Request::setTestBody(json_encode([
            'mensaje' => 'Mensaje actualizado'
        ]));

        $this->service
            ->expects($this->once())
            ->method('actualizarConsulta')
            ->with(
                1,
                [
                    'mensaje' => 'Mensaje actualizado'
                ],
                1
            )
            ->willReturn(true);

        ob_start();

        try {
            $this->controller->update(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode($output, true);

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Consulta actualizada exitosamente',
            $response['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_eliminar_una_consulta(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminarConsulta')
            ->with(1, 1)
            ->willReturn(true);

        ob_start();

        try {
            $this->controller->delete(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode($output, true);

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Consulta eliminada exitosamente',
            $response['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_restaurar_una_consulta(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurarConsulta')
            ->with(1, 1)
            ->willReturn(true);

        ob_start();

        try {
            $this->controller->restore(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode($output, true);

        $this->assertTrue($response['success']);
        $this->assertSame(
            'Consulta restaurada exitosamente',
            $response['message']
        );
        $this->assertSame(200, http_response_code());
    }
}