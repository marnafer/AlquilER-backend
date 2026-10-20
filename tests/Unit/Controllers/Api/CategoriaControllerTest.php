<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use App\Controllers\Api\CategoriaController;
use App\Models\Categoria;
use App\Services\CategoriaService;
use Tests\TestCase;

final class CategoriaControllerTest extends TestCase
{
    private CategoriaService $service;
    private CategoriaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(CategoriaService::class);
        $this->controller = new CategoriaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 2))
        );
    }

    public function test_index_lista_las_categorias(): void
    {
        $categorias = [['id' => 1, 'nombre' => 'Departamento']];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn($categorias);

        $response = $this->controller->index($this->makeRequest());

        $this->assertSame(200, $response->status());
        $this->assertSame($categorias, $response->payload()['data']);
    }

    public function test_index_pasa_los_filtros_de_papelera(): void
    {
        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with(['solo_eliminados' => true])
            ->willReturn([]);

        $this->controller->index($this->makeRequest([
            'query' => ['solo_eliminados' => '1'],
        ]));

        $this->addToAssertionCount(1);
    }

    public function test_index_rechaza_a_un_no_administrador_en_papelera(): void
    {
        $controller = new CategoriaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('listar');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->index($this->makeRequest([
            'query' => ['solo_eliminados' => '1'],
        ]));
    }

    public function test_show_devuelve_una_categoria(): void
    {
        $categoria = new Categoria(['id' => 1, 'nombre' => 'Departamento']);

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($categoria);

        $response = $this->controller->show($this->makeRequest(), 1);

        $this->assertSame(200, $response->status());
        $this->assertSame($categoria, $response->payload()['data']);
    }

    public function test_store_crea_una_categoria(): void
    {
        $categoria = new Categoria(['id' => 9, 'nombre' => 'Casa']);

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with(['nombre' => 'Casa'])
            ->willReturn($categoria);

        $response = $this->controller->store($this->makeJsonRequest([
            'nombre' => 'Casa',
        ]));

        $this->assertSame(201, $response->status());
        $this->assertSame($categoria, $response->payload()['data']);
        $this->assertSame(
            'Categoría creada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_store_rechaza_a_un_no_administrador(): void
    {
        $controller = new CategoriaController(
            $this->service,
            $this->authenticator($this->usuarioFalso(5, 1))
        );

        $this->service->expects($this->never())->method('crear');

        $this->expectException(\App\Exceptions\ForbiddenException::class);

        $controller->store($this->makeJsonRequest(['nombre' => 'X']));
    }

    public function test_update_actualiza_una_categoria(): void
    {
        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(3, ['nombre' => 'Modificada']);

        $response = $this->controller->update(
            $this->makeJsonRequest(['nombre' => 'Modificada']),
            3
        );

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Categoría actualizada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_delete_elimina_una_categoria(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(3);

        $response = $this->controller->delete($this->makeRequest(), 3);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Categoría eliminada exitosamente',
            $response->payload()['message']
        );
    }

    public function test_restore_restaura_una_categoria(): void
    {
        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(3);

        $response = $this->controller->restore($this->makeRequest(), 3);

        $this->assertSame(200, $response->status());
        $this->assertSame(
            'Categoría restaurada exitosamente',
            $response->payload()['message']
        );
    }
}
