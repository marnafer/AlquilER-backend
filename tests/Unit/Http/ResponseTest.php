<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function test_success_es_200_con_success_y_data(): void
    {
        $response = Response::success(['id' => 1]);

        $this->assertSame(200, $response->status());
        $this->assertTrue($response->payload()['success']);
        $this->assertSame(['id' => 1], $response->payload()['data']);
        $this->assertArrayNotHasKey('message', $response->payload());
    }

    public function test_success_acepta_un_status_y_mensaje(): void
    {
        $response = Response::success([], 200, 'Listo');

        $this->assertSame(200, $response->status());
        $this->assertSame('Listo', $response->payload()['message']);
    }

    public function test_created_es_201(): void
    {
        $response = Response::created(['id' => 9], 'Creado');

        $this->assertSame(201, $response->status());
        $this->assertTrue($response->payload()['success']);
        $this->assertSame(['id' => 9], $response->payload()['data']);
        $this->assertSame('Creado', $response->payload()['message']);
    }

    public function test_no_content_es_204_sin_payload(): void
    {
        $response = Response::noContent();

        $this->assertSame(204, $response->status());
        $this->assertNull($response->payload());
    }

    public function test_validation_error_es_422_con_errores(): void
    {
        $response = Response::validationError(['email' => 'requerido']);

        $this->assertSame(422, $response->status());
        $this->assertFalse($response->payload()['success']);
        $this->assertSame('Error de validación', $response->payload()['error']);
        $this->assertSame(
            ['email' => 'requerido'],
            $response->payload()['validation_errors']
        );
    }

    public static function proveedorErrores(): array
    {
        return [
            'notFound' => [fn() => Response::notFound(), 404, 'Recurso no encontrado'],
            'notFoundCustom' => [fn() => Response::notFound('Nada'), 404, 'Nada'],
            'methodNotAllowed' => [fn() => Response::methodNotAllowed(), 405, 'Método no permitido'],
            'unauthorized' => [fn() => Response::unauthorized(), 401, 'No autorizado'],
            'unauthorizedCustom' => [fn() => Response::unauthorized('Token requerido'), 401, 'Token requerido'],
            'forbidden' => [fn() => Response::forbidden(), 403, 'Acceso denegado'],
            'forbiddenCustom' => [fn() => Response::forbidden('Solo admin'), 403, 'Solo admin'],
            'badRequest' => [fn() => Response::badRequest(), 400, 'Solicitud inválida'],
            'badRequestCustom' => [fn() => Response::badRequest('Mal'), 400, 'Mal'],
            'serverError' => [fn() => Response::serverError(), 500, 'Error interno del servidor'],
            'serverErrorCustom' => [fn() => Response::serverError('Boom'), 500, 'Boom'],
        ];
    }

    #[DataProvider('proveedorErrores')]
    public function test_fabricas_de_error(
        callable $factory,
        int $status,
        string $mensaje
    ): void {
        $response = $factory();

        $this->assertSame($status, $response->status());
        $this->assertFalse($response->payload()['success']);
        $this->assertSame($mensaje, $response->payload()['error']);
        $this->assertArrayNotHasKey('data', $response->payload());
    }

    public function test_json_permite_un_payload_libre(): void
    {
        $response = Response::json(['foo' => 'bar'], 207);

        $this->assertSame(207, $response->status());
        $this->assertSame(['foo' => 'bar'], $response->payload());
    }

    public function test_headers_por_defecto_estan_vacios(): void
    {
        $response = Response::success();

        $this->assertSame([], $response->headers());
    }

    public function test_with_header_devuelve_una_copia(): void
    {
        $original = Response::success();
        $conHeader = $original->withHeader('Retry-After', '30');

        $this->assertNotSame($original, $conHeader);
        $this->assertSame(
            ['Retry-After' => '30'],
            $conHeader->headers()
        );
        $this->assertSame([], $original->headers());
    }

    public function test_with_header_preserva_status_y_payload(): void
    {
        $response = Response::success(['a' => 1])
            ->withHeader('X-Foo', 'bar');

        $this->assertSame(200, $response->status());
        $this->assertSame(['a' => 1], $response->payload()['data']);
        $this->assertSame(['X-Foo' => 'bar'], $response->headers());
    }
}
