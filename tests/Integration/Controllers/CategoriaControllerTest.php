<?php

namespace Tests\Integration\Controllers;

use Tests\TestCase;
use App\Controllers\Api\CategoriaController;
use App\Services\CategoriaService;
use App\Helpers\Request;
use App\Helpers\TokenProviderInterface;
use App\Middlewares\AutenticadorMiddleware;
use App\Models\Categoria;

class CategoriaControllerTest extends TestCase
{
    private $controller;
    private $service;
    private $tokenProviderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(
            CategoriaService::class
        );

        $this->controller = new CategoriaController(
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
                    'rol_id' => 2
                ]
            );

        AutenticadorMiddleware::configure(
            $this->tokenProviderMock
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_admin';
    }

    protected function tearDown(): void
    {
        Request::setTestBody(null);

        unset(
            $_SERVER['HTTP_AUTHORIZATION']
        );

        parent::tearDown();
    }

    public function test_el_controlador_puede_listar_categorias(): void
    {
        $categorias = [
            ['id' => 1, 'nombre' => 'Casa'],
            ['id' => 2, 'nombre' => 'Departamento']
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn($categorias);

        ob_start();

        try {
            $this->controller->index();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            $categorias,
            $response['data']
        );

        $this->assertSame(
            200,
            http_response_code()
        );
    }

    public function test_el_controlador_puede_obtener_una_categoria(): void
    {
        $categoria = new Categoria([
            'id' => 1,
            'nombre' => 'Casa'
        ]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($categoria);

        ob_start();

        try {
            $this->controller->show(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertArrayHasKey(
            'data',
            $response
        );

        $this->assertSame(
            200,
            http_response_code()
        );
    }

    public function test_el_controlador_puede_crear_una_categoria(): void
    {
        Request::setTestBody(
            json_encode([
                'nombre' => 'Nueva Categoria'
            ])
        );

        $categoria = new Categoria([
            'id' => 5,
            'nombre' => 'Nueva Categoria'
        ]);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([
                'nombre' => 'Nueva Categoria'
            ])
            ->willReturn($categoria);

        ob_start();

        try {
            $this->controller->store();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            'Categoría creada exitosamente',
            $response['message']
        );

        $this->assertSame(
            201,
            http_response_code()
        );
    }

    public function test_el_controlador_puede_actualizar_una_categoria(): void
    {
        Request::setTestBody(
            json_encode([
                'nombre' => 'Categoria Actualizada'
            ])
        );

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                [
                    'nombre' => 'Categoria Actualizada'
                ]
            );

        ob_start();

        try {
            $this->controller->update(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            'Categoría actualizada exitosamente',
            $response['message']
        );

        $this->assertSame(
            200,
            http_response_code()
        );
    }

    public function test_el_controlador_puede_eliminar_una_categoria(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1);

        ob_start();

        try {
            $this->controller->delete(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            'Categoría eliminada exitosamente',
            $response['message']
        );

        $this->assertSame(
            200,
            http_response_code()
        );
    }

    public function test_el_controlador_puede_restaurar_una_categoria(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1);

        ob_start();

        try {
            $this->controller->restore(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );

        $this->assertSame(
            'Categoría restaurada exitosamente',
            $response['message']
        );

        $this->assertSame(
            200,
            http_response_code()
        );
    }

    public function test_el_controlador_verifica_autenticacion_de_admin_al_crear(): void
    {
        $this->tokenProviderMock
            ->expects($this->once())
            ->method('validate')
            ->with('token_admin')
            ->willReturn(
                (object) [
                    'sub' => 1,
                    'rol_id' => 2
                ]
            );

        Request::setTestBody(
            json_encode([
                'nombre' => 'Nueva Categoria'
            ])
        );

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with([
                'nombre' => 'Nueva Categoria'
            ])
            ->willReturn(
                new Categoria([
                    'id' => 5,
                    'nombre' => 'Nueva Categoria'
                ])
            );

        ob_start();

        try {
            $this->controller->store();
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );
    }

    public function test_el_controlador_verifica_autenticacion_de_admin_al_actualizar(): void
    {
        $this->tokenProviderMock
            ->expects($this->once())
            ->method('validate')
            ->with('token_admin')
            ->willReturn(
                (object) [
                    'sub' => 1,
                    'rol_id' => 2
                ]
            );

        Request::setTestBody(
            json_encode([
                'nombre' => 'Categoria Actualizada'
            ])
        );

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(
                1,
                [
                    'nombre' => 'Categoria Actualizada'
                ]
            );

        ob_start();

        try {
            $this->controller->update(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );
    }

    public function test_el_controlador_verifica_autenticacion_de_admin_al_eliminar(): void
    {
        $this->tokenProviderMock
            ->expects($this->once())
            ->method('validate')
            ->with('token_admin')
            ->willReturn(
                (object) [
                    'sub' => 1,
                    'rol_id' => 2
                ]
            );

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1);

        ob_start();

        try {
            $this->controller->delete(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );
    }

    public function test_el_controlador_verifica_autenticacion_de_admin_al_restaurar(): void
    {
        $this->tokenProviderMock
            ->expects($this->once())
            ->method('validate')
            ->with('token_admin')
            ->willReturn(
                (object) [
                    'sub' => 1,
                    'rol_id' => 2
                ]
            );

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1);

        ob_start();

        try {
            $this->controller->restore(1);
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertJson($output);

        $response = json_decode(
            $output,
            true
        );

        $this->assertTrue(
            $response['success']
        );
    }
}