<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\FavoritoController;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Services\FavoritoService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class FavoritoControllerTest extends TestCase
{
    /** @var FavoritoService&MockObject */
    private FavoritoService $service;

    private FavoritoController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        Response::setTesting(true);

        $this->service = $this->createMock(
            FavoritoService::class
        );

        $this->controller = new FavoritoController(
            $this->service
        );

        $this->actingAs(5, 1);
    }

    protected function tearDown(): void
    {
        Request::setTestBody(null);

        unset($_SERVER['HTTP_AUTHORIZATION']);

        Response::setTesting(false);

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function test_el_controlador_puede_listar_los_favoritos_del_usuario(): void
    {
        $favoritos = [
            [
                'id' => 10,
                'usuario_id' => 5,
                'propiedad_id' => 20
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(5, 5, 1)
            ->willReturn($favoritos);

        $response = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($response['success']);
        $this->assertSame($favoritos, $response['data']);
        $this->assertSame(200, http_response_code());
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function test_el_controlador_puede_agregar_una_propiedad_a_favoritos(): void
    {
        $datos = [
            'propiedad_id' => 20
        ];

        Request::setTestBody(
            json_encode($datos, JSON_THROW_ON_ERROR)
        );

        $this->service
            ->expects($this->once())
            ->method('agregar')
            ->with($datos, 5)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->store()
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            20,
            $response['data']['propiedad_id']
        );

        $this->assertTrue(
            $response['data']['es_favorito']
        );

        $this->assertSame(
            'Propiedad agregada a favoritos',
            $response['message']
        );

        $this->assertSame(201, http_response_code());
    }

    public function test_el_controlador_verifica_el_token_al_agregar_favorito(): void
    {
        $datos = [
            'propiedad_id' => 20
        ];

        $tokenProvider = $this->createMock(
            \App\Helpers\TokenProviderInterface::class
        );

        $tokenProvider
            ->expects($this->once())
            ->method('validate')
            ->with('token_usuario')
            ->willReturn((object) [
                'sub' => 5,
                'rol_id' => 1,
                'email' => 'test@test.com'
            ]);

        AutenticadorMiddleware::configure(
            $tokenProvider
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_usuario';

        Request::setTestBody(
            json_encode($datos, JSON_THROW_ON_ERROR)
        );

        $this->service
            ->expects($this->once())
            ->method('agregar')
            ->with($datos, 5)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->store()
        );

        $this->assertTrue(
            $response['success'] ?? false,
            json_encode(
                $response,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX BY USUARIO
    |--------------------------------------------------------------------------
    */

    public function test_el_controlador_puede_obtener_favoritos_de_otro_usuario_como_admin(): void
    {
        $favoritos = [
            [
                'id' => 10,
                'usuario_id' => 8,
                'propiedad_id' => 20
            ]
        ];

        $this->actingAs(5, 2);

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(5, 8, 2)
            ->willReturn($favoritos);

        $response = $this->captureJson(
            fn() => $this->controller->indexByUsuario(8)
        );

        $this->assertTrue($response['success']);
        $this->assertSame($favoritos, $response['data']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_verifica_el_token_al_obtener_favoritos_de_usuario(): void
    {
        $tokenProvider = $this->createMock(
            \App\Helpers\TokenProviderInterface::class
        );

        $tokenProvider
            ->expects($this->once())
            ->method('validate')
            ->with('token_usuario')
            ->willReturn((object) [
                'sub' => 5,
                'rol_id' => 1,
                'email' => 'test@test.com'
            ]);

        AutenticadorMiddleware::configure(
            $tokenProvider
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_usuario';

        $favoritos = [
            [
                'id' => 10,
                'usuario_id' => 5,
                'propiedad_id' => 20
            ]
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(5, 5, 1)
            ->willReturn($favoritos);

        $response = $this->captureJson(
            fn() => $this->controller->indexByUsuario(5)
        );

        $this->assertTrue(
            $response['success'] ?? false,
            json_encode(
                $response,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE BY PROPIEDAD
    |--------------------------------------------------------------------------
    */

    public function test_el_controlador_puede_eliminar_un_favorito(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(20, 5)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->deleteByPropiedad(20)
        );

        $this->assertTrue($response['success']);

        $this->assertSame(
            'Propiedad eliminada de favoritos',
            $response['message']
        );

        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_verifica_el_token_al_eliminar_favorito(): void
    {
        $tokenProvider = $this->createMock(
            \App\Helpers\TokenProviderInterface::class
        );

        $tokenProvider
            ->expects($this->once())
            ->method('validate')
            ->with('token_usuario')
            ->willReturn((object) [
                'sub' => 5,
                'rol_id' => 1,
                'email' => 'test@test.com'
            ]);

        AutenticadorMiddleware::configure(
            $tokenProvider
        );

        $_SERVER['HTTP_AUTHORIZATION'] =
            'Bearer token_usuario';

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(20, 5)
            ->willReturn(true);

        $response = $this->captureJson(
            fn() => $this->controller->deleteByPropiedad(20)
        );

        $this->assertTrue(
            $response['success'] ?? false,
            json_encode(
                $response,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }
}