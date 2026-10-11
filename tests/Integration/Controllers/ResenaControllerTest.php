<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\Api\ResenaController;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Models\Resena;
use App\Models\Rol;
use App\Services\ResenaService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class ResenaControllerTest extends TestCase
{
    /** @var ResenaService&MockObject */
    private ResenaService $service;

    private ResenaController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        Response::setTesting(true);

        // Evita reutilizar encabezados de otros tests.
        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        $_GET = [];
        http_response_code(200);

        $this->service = $this->createMock(ResenaService::class);
        $this->controller = new ResenaController($this->service);

        // Configura un usuario autenticado para los tests por defecto.
        $this->actingAs(1, 1);
    }

    protected function tearDown(): void
    {
        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        $_GET = [];

        Request::setTestBody(null);

        Response::setTesting(false);

        parent::tearDown();
    }

    public function test_el_controlador_puede_listar_resenas(): void
    {
        $resultado = [
            'items' => [],
            'total' => 0,
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with([])
            ->willReturn($resultado);

        $respuesta = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame($resultado, $respuesta['data']);
        $this->assertSame(
            'Reseñas obtenidas correctamente',
            $respuesta['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_listar_resenas_con_filtros(): void
    {

        $this->actingAs(2, Rol::ADMIN);

        $_GET = [
            'tipo' => 'propiedad',
            'calificacion' => '5',
            'calificacion_min' => '3',
            'calificacion_max' => '5',
            'calificador_id' => '10',
            'reserva_id' => '20',
            'propiedad_id' => '30',
            'usuario_id' => '40',
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => '2026-12-31',
            'solo_eliminados' => 'false',
            'incluir_eliminados' => 'true',
        ];

        $filtrosEsperados = [
            'tipo' => 'propiedad',
            'calificacion' => 5,
            'calificacion_min' => 3,
            'calificacion_max' => 5,
            'calificador_id' => 10,
            'reserva_id' => 20,
            'propiedad_id' => 30,
            'usuario_id' => 40,
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => '2026-12-31',
            'solo_eliminados' => false,
            'incluir_eliminados' => true,
        ];

        $resultado = [
            'items' => [],
            'total' => 0,
        ];

        $this->service
            ->expects($this->once())
            ->method('listar')
            ->with($filtrosEsperados)
            ->willReturn($resultado);

        $respuesta = $this->captureJson(
            fn() => $this->controller->index()
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame($resultado, $respuesta['data']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_una_resena(): void
    {
        $resena = new Resena();

        $this->service
            ->expects($this->once())
            ->method('obtener')
            ->with(1)
            ->willReturn($resena);

        $respuesta = $this->captureJson(
            fn() => $this->controller->show(1)
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame('Reseña encontrada', $respuesta['message']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_resenas_por_reserva(): void
    {
        $resenas = new Collection([
            ['id' => 1, 'reserva_id' => 10],
            ['id' => 2, 'reserva_id' => 10],
        ]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPorReserva')
            ->with(10)
            ->willReturn($resenas);

        $respuesta = $this->captureJson(
            fn() => $this->controller->getByReserva(10)
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame(
            'Reseñas de la reserva obtenidas',
            $respuesta['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_resenas_por_propiedad(): void
    {
        $resenas = new Collection([
            ['id' => 1, 'propiedad_id' => 30],
            ['id' => 2, 'propiedad_id' => 30],
        ]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPorPropiedad')
            ->with(30)
            ->willReturn($resenas);

        $this->service
            ->expects($this->once())
            ->method('promedioPropiedad')
            ->with(30)
            ->willReturn(4.5);

        $respuesta = $this->captureJson(
            fn() => $this->controller->getByPropiedad(30)
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame(2, $respuesta['data']['total']);
        $this->assertSame(4.5, $respuesta['data']['promedio']);
        $this->assertCount(2, $respuesta['data']['items']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_resenas_por_usuario(): void
    {
        $resenas = new Collection([
            ['id' => 1, 'usuario_id' => 40],
            ['id' => 2, 'usuario_id' => 40],
        ]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPorUsuario')
            ->with(40)
            ->willReturn($resenas);

        $this->service
            ->expects($this->once())
            ->method('promedioUsuario')
            ->with(40)
            ->willReturn(4.0);

        $respuesta = $this->captureJson(
            fn() => $this->controller->getByUsuario(40)
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame(2, $respuesta['data']['total']);
        $this->assertEquals(4.0, $respuesta['data']['promedio']);
        $this->assertCount(2, $respuesta['data']['items']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_obtener_resenas_por_calificador(): void
    {
        $resenas = new Collection([
            ['id' => 1, 'calificador_id' => 50],
            ['id' => 2, 'calificador_id' => 50],
        ]);

        $this->service
            ->expects($this->once())
            ->method('obtenerPorCalificador')
            ->with(50)
            ->willReturn($resenas);

        $respuesta = $this->captureJson(
            fn() => $this->controller->getByCalificador(50)
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame(
            'Reseñas del calificador obtenidas',
            $respuesta['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_crear_una_resena(): void
    {
        $datos = [
            'reserva_id' => 10,
            'calificacion' => 5,
            'comentario' => 'Excelente propiedad',
        ];

        $resena = new Resena();

        $this->service
            ->expects($this->once())
            ->method('crear')
            ->with($datos, 1)
            ->willReturn($resena);

        $respuesta = $this->captureJsonWithBody(
            json_encode($datos, JSON_THROW_ON_ERROR),
            fn() => $this->controller->store()
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame(
            'Reseña creada exitosamente',
            $respuesta['message']
        );
        $this->assertSame(201, http_response_code());
    }

    public function test_el_controlador_no_puede_crear_una_resena_sin_token(): void
    {
        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        $datos = [
            'reserva_id' => 10,
            'calificacion' => 5,
            'comentario' => 'Excelente propiedad',
        ];

        $this->service
            ->expects($this->never())
            ->method('crear');

        $respuesta = $this->captureJsonWithBody(
            json_encode($datos, JSON_THROW_ON_ERROR),
            fn() => $this->controller->store()
        );

        $this->assertFalse($respuesta['success']);
        $this->assertSame(401, http_response_code());
    }

    public function test_el_controlador_puede_crear_una_resena_como_administrador(): void
    {
        $this->actingAs(2, Rol::ADMIN);

        $datos = [
            'reserva_id' => 10,
            'calificacion' => 5,
            'comentario' => 'Reseña registrada por administración',
        ];

        $resena = new Resena();

        $this->service
            ->expects($this->once())
            ->method('crearDesdeAdmin')
            ->with($datos, 2)
            ->willReturn($resena);

        $respuesta = $this->captureJsonWithBody(
            json_encode($datos, JSON_THROW_ON_ERROR),
            fn() => $this->controller->storeAdmin()
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame(
            'Reseña creada correctamente',
            $respuesta['message']
        );
        $this->assertSame(201, http_response_code());
    }

    public function test_el_controlador_no_permite_crear_resenas_como_administrador_a_un_usuario_comun(): void
    {
        $this->actingAs(1, 1);

        $datos = [
            'reserva_id' => 10,
            'calificacion' => 5,
            'comentario' => 'Reseña de prueba',
        ];

        $this->service
            ->expects($this->never())
            ->method('crearDesdeAdmin');

        $respuesta = $this->captureJsonWithBody(
            json_encode($datos, JSON_THROW_ON_ERROR),
            fn() => $this->controller->storeAdmin()
        );

        $this->assertFalse($respuesta['success']);
        $this->assertSame(403, http_response_code());
    }

    public function test_el_controlador_puede_actualizar_una_resena(): void
    {
        $datos = [
            'calificacion' => 4,
            'comentario' => 'Muy buena propiedad',
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(1, $datos, 1, 1);

        $respuesta = $this->captureJsonWithBody(
            json_encode($datos, JSON_THROW_ON_ERROR),
            fn() => $this->controller->update(1)
        );

        $this->assertTrue($respuesta['success']);
        $this->assertNull($respuesta['data']);
        $this->assertSame(
            'Reseña actualizada correctamente',
            $respuesta['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_eliminar_una_resena(): void
    {
        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(1, 1, 1);

        $respuesta = $this->captureJson(
            fn() => $this->controller->delete(1)
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame(
            'Reseña eliminada exitosamente',
            $respuesta['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_puede_restaurar_una_resena(): void
    {
        $this->actingAs(2, Rol::ADMIN);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(1, 2, Rol::ADMIN);

        $respuesta = $this->captureJson(
            fn() => $this->controller->restore(1)
        );

        $this->assertTrue($respuesta['success']);
        $this->assertNull($respuesta['data']);
        $this->assertSame(
            'Reseña restaurada exitosamente',
            $respuesta['message']
        );
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_pasa_el_usuario_y_el_rol_al_eliminar(): void
    {
        $this->actingAs(2, Rol::ADMIN);

        $this->service
            ->expects($this->once())
            ->method('eliminar')
            ->with(7, 2, Rol::ADMIN);

        $respuesta = $this->captureJson(
            fn() => $this->controller->delete(7)
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_pasa_el_usuario_y_el_rol_al_actualizar(): void
    {
        $this->actingAs(2, Rol::ADMIN);

        $datos = [
            'calificacion' => 3,
            'comentario' => 'Actualizada por administración',
        ];

        $this->service
            ->expects($this->once())
            ->method('actualizar')
            ->with(7, $datos, 2, Rol::ADMIN);

        $respuesta = $this->captureJsonWithBody(
            json_encode($datos, JSON_THROW_ON_ERROR),
            fn() => $this->controller->update(7)
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame(200, http_response_code());
    }

    public function test_el_controlador_pasa_el_usuario_y_el_rol_al_restaurar(): void
    {
        $this->actingAs(2, Rol::ADMIN);

        $this->service
            ->expects($this->once())
            ->method('restaurar')
            ->with(7, 2, Rol::ADMIN);

        $respuesta = $this->captureJson(
            fn() => $this->controller->restore(7)
        );

        $this->assertTrue($respuesta['success']);
        $this->assertSame(200, http_response_code());
    }
}
