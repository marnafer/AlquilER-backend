<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\PropiedadImagenController;
use App\Services\PropiedadImagenService;
use Tests\TestCase;

final class PropiedadImagenControllerTest extends TestCase
{
    private PropiedadImagenService $service;
    private PropiedadImagenController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(PropiedadImagenService::class);
        $this->controller = new PropiedadImagenController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );
    }

    public function test_index_lista_las_imagenes_sin_autenticacion(): void
    {
        $imagenes = [['id' => 1, 'url' => 'a.jpg']];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->willReturn($imagenes);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame($imagenes, $response->payload()['data']);
    }

    public function test_show_devuelve_una_imagen_sin_autenticacion(): void
    {
        $imagen = new \App\Models\PropiedadImagen(['id' => 4]);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(4)
            ->willReturn($imagen);

        $response = $this->controller->show($this->makeRequest(), 4);

        $this->assertSame(200, $response->status());
        $this->assertSame($imagen, $response->payload()['data']);
    }

    public function test_store_usa_post_params_y_el_archivo(): void
    {
        $archivo = ['name' => 'foto.jpg', 'tmp_name' => '/tmp/x'];

        $imagen = new \App\Models\PropiedadImagen(['id' => 77]);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(
                ['propiedad_id' => '1'],
                $archivo,
                5,
                1
            )
            ->willReturn($imagen);

        $response = $this->controller->store($this->makeRequest([
            'method' => 'POST',
            'post' => ['propiedad_id' => '1'],
            'files' => ['imagen' => $archivo],
        ]));

        $this->assertSame(201, $response->status());
        $this->assertSame($imagen, $response->payload()['data']);
        $this->assertSame(
            'Imagen guardada correctamente',
            $response->payload()['message']
        );
    }

    public function test_store_requiere_autenticacion(): void
    {
        $controller = new PropiedadImagenController(
            $this->service,
            $this->authenticator()
        );

        $this->service->expects($this->never())->method('crear');

        $this->expectException(\App\Exceptions\UnauthorizedException::class);

        $controller->store($this->makeRequest(['method' => 'POST']));
    }

    public function test_set_principal_devuelve_200(): void
    {
        $this->service
            ->expects($this->once())
            ->method('establecerPrincipal')
            ->with(8, 5, 1)
            ->willReturn(
                new \App\Models\PropiedadImagen([
                    'id' => 8,
                    'es_principal' => true,
                ])
            );

        $response = $this->controller->setPrincipal($this->makeRequest(), 8);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Imagen principal actualizada',
            $response->payload()['message']
        );
    }

    public function test_delete_elimina_una_imagen(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(8, 5, 1);

        $response = $this->controller->delete($this->makeRequest(), 8);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Imagen eliminada correctamente',
            $response->payload()['message']
        );
    }
}
