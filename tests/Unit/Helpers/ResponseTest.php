<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\Response;
use ReflectionProperty;
use Tests\TestCase;

class ResponseTest extends TestCase
{
    public function test_success_con_mensaje(): void
    {
        $response = $this->captureJson(
            fn() => Response::success(['id' => 1], 200, 'ok')
        );

        $this->assertTrue($response['success']);
        $this->assertSame(['id' => 1], $response['data']);
        $this->assertSame('ok', $response['message']);
        $this->assertSame(200, http_response_code());
    }

    public function test_success_sin_mensaje_no_incluye_la_clave_message(): void
    {
        $response = $this->captureJson(
            fn() => Response::success([])
        );

        $this->assertTrue($response['success']);
        $this->assertArrayNotHasKey('message', $response);
    }

    public function test_success_con_estado_personalizado(): void
    {
        $this->captureJson(fn() => Response::success([], 202));

        $this->assertSame(202, http_response_code());
    }

    public function test_created_devuelve_201(): void
    {
        $response = $this->captureJson(
            fn() => Response::created(['id' => 7], 'creado')
        );

        $this->assertTrue($response['success']);
        $this->assertSame(['id' => 7], $response['data']);
        $this->assertSame('creado', $response['message']);
        $this->assertSame(201, http_response_code());
    }

    public function test_no_content_devuelve_204_sin_cuerpo(): void
    {
        $body = $this->captureResponse(fn() => Response::noContent());

        $this->assertSame('', $body);
        $this->assertSame(204, http_response_code());
    }

    public function test_validation_error_devuelve_422(): void
    {
        $errores = ['email' => ['El email es obligatorio']];

        $response = $this->captureJson(
            fn() => Response::validationError($errores)
        );

        $this->assertFalse($response['success']);
        $this->assertSame('Error de validación', $response['error']);
        $this->assertSame($errores, $response['validation_errors']);
        $this->assertSame(422, http_response_code());
    }

    public function test_not_found_usa_el_mensaje_por_defecto(): void
    {
        $response = $this->captureJson(fn() => Response::notFound());

        $this->assertFalse($response['success']);
        $this->assertSame('Recurso no encontrado', $response['error']);
        $this->assertSame(404, http_response_code());
    }

    public function test_not_found_acepta_un_mensaje_personalizado(): void
    {
        $response = $this->captureJson(
            fn() => Response::notFound('No existe')
        );

        $this->assertSame('No existe', $response['error']);
        $this->assertSame(404, http_response_code());
    }

    public function test_method_not_allowed_devuelve_405(): void
    {
        $response = $this->captureJson(
            fn() => Response::methodNotAllowed()
        );

        $this->assertFalse($response['success']);
        $this->assertSame('Método no permitido', $response['error']);
        $this->assertSame(405, http_response_code());
    }

    public function test_unauthorized_usa_el_mensaje_por_defecto(): void
    {
        $response = $this->captureJson(fn() => Response::unauthorized());

        $this->assertSame('No autorizado', $response['error']);
        $this->assertSame(401, http_response_code());
    }

    public function test_unauthorized_acepta_un_mensaje_personalizado(): void
    {
        $response = $this->captureJson(
            fn() => Response::unauthorized('Token vencido')
        );

        $this->assertSame('Token vencido', $response['error']);
        $this->assertSame(401, http_response_code());
    }

    public function test_forbidden_usa_el_mensaje_por_defecto(): void
    {
        $response = $this->captureJson(fn() => Response::forbidden());

        $this->assertSame('Acceso denegado', $response['error']);
        $this->assertSame(403, http_response_code());
    }

    public function test_forbidden_acepta_un_mensaje_personalizado(): void
    {
        $response = $this->captureJson(
            fn() => Response::forbidden('Sin permisos')
        );

        $this->assertSame('Sin permisos', $response['error']);
        $this->assertSame(403, http_response_code());
    }

    public function test_bad_request_usa_el_mensaje_por_defecto(): void
    {
        $response = $this->captureJson(fn() => Response::badRequest());

        $this->assertSame('Solicitud inválida', $response['error']);
        $this->assertSame(400, http_response_code());
    }

    public function test_bad_request_acepta_un_mensaje_personalizado(): void
    {
        $response = $this->captureJson(
            fn() => Response::badRequest('Faltan datos')
        );

        $this->assertSame('Faltan datos', $response['error']);
        $this->assertSame(400, http_response_code());
    }

    public function test_server_error_usa_el_mensaje_por_defecto(): void
    {
        $response = $this->captureJson(fn() => Response::serverError());

        $this->assertSame(
            'Error interno del servidor',
            $response['error']
        );
        $this->assertSame(500, http_response_code());
    }

    public function test_server_error_acepta_un_mensaje_personalizado(): void
    {
        $response = $this->captureJson(
            fn() => Response::serverError('Exploto')
        );

        $this->assertSame('Exploto', $response['error']);
        $this->assertSame(500, http_response_code());
    }

    public function test_json_con_datos_no_codificables_devuelve_500(): void
    {
        // Cadena con bytes UTF-8 invalidos: json_encode lanza JsonException.
        $response = $this->captureJson(
            fn() => Response::json(["\xB1\x31"])
        );

        $this->assertFalse($response['success']);
        $this->assertSame('Error al generar JSON', $response['error']);
        $this->assertSame(500, http_response_code());
    }

    public function test_set_testing_cambia_el_estado_interno(): void
    {
        $propiedad = new \ReflectionProperty(Response::class, 'testing');

        Response::setTesting(true);
        $this->assertTrue($propiedad->getValue());

        Response::setTesting(false);
        $this->assertFalse($propiedad->getValue());
    }
}
