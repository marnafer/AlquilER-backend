<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Exceptions\BadRequestException;
use App\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function test_accesores_devuelven_lo_inyectado(): void
    {
        $request = new Request(
            method: 'POST',
            path: '/api/propiedades',
            query: ['page' => '2'],
            body: '{"nombre":"Casa"}',
            post: ['campo' => 'valor'],
            files: ['imagen' => ['name' => 'a.jpg']],
            headers: ['Content-Type' => 'application/json']
        );

        $this->assertSame('POST', $request->method());
        $this->assertSame('/api/propiedades', $request->path());
        $this->assertSame('2', $request->query('page'));
        $this->assertSame('Casa', $request->input('nombre'));
        $this->assertSame('valor', $request->post('campo'));
        $this->assertSame(
            ['name' => 'a.jpg'],
            $request->file('imagen')
        );
        $this->assertSame(
            'application/json',
            $request->header('Content-Type')
        );
    }

    public function test_por_defecto_es_get_en_raiz_sin_parametros(): void
    {
        $request = new Request();

        $this->assertSame('GET', $request->method());
        $this->assertSame('/', $request->path());
        $this->assertNull($request->query('nada'));
        $this->assertNull($request->header('Authorization'));
        $this->assertNull($request->file('nada'));
    }

    public function test_query_devuelve_el_default_cuando_no_existe(): void
    {
        $request = new Request(query: ['a' => '1']);

        $this->assertSame('defecto', $request->query('b', 'defecto'));
    }

    public function test_query_params_devuelve_el_mapa_completo(): void
    {
        $request = new Request(query: ['a' => '1', 'b' => '2']);

        $this->assertSame(['a' => '1', 'b' => '2'], $request->queryParams());
    }

    public function test_post_params_devuelve_el_mapa_completo(): void
    {
        $request = new Request(post: ['x' => '1']);

        $this->assertSame(['x' => '1'], $request->postParams());
    }

    public function test_header_es_case_insensitive(): void
    {
        $request = new Request(headers: ['Content-Type' => 'text/plain']);

        $this->assertSame('text/plain', $request->header('content-type'));
        $this->assertSame('text/plain', $request->header('CONTENT-TYPE'));
    }

    public function test_raw_body_permite_leer_el_body_crudo(): void
    {
        $request = new Request(body: '{"a":1}');

        $this->assertSame('{"a":1}', $request->rawBody());
    }

    // ===== json() =====

    public function test_json_decodifica_un_objeto(): void
    {
        $request = new Request(body: '{"nombre":"Casa","precio":100}');

        $this->assertSame(
            ['nombre' => 'Casa', 'precio' => 100],
            $request->json()
        );
    }

    public function test_json_acepta_un_arreglo_vacio(): void
    {
        $request = new Request(body: '[]');

        $this->assertSame([], $request->json());
    }

    public function test_json_lanza_si_no_hay_body(): void
    {
        $request = new Request();

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El cuerpo de la solicitud es obligatorio');

        $request->json();
    }

    public function test_json_lanza_si_el_body_es_vacio_o_en_blanco(): void
    {
        $request = new Request(body: '   ');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El cuerpo de la solicitud es obligatorio');

        $request->json();
    }

    public function test_json_lanza_si_el_body_es_invalido(): void
    {
        $request = new Request(body: '{no es json');

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El JSON enviado no es válido');

        $request->json();
    }

    public static function proveedorNoObjeto(): array
    {
        return [
            'texto' => ['"hola"'],
            'numero' => ['42'],
            'booleano' => ['true'],
            'nulo' => ['null'],
        ];
    }

    #[DataProvider('proveedorNoObjeto')]
    public function test_json_lanza_si_el_json_no_es_un_arreglo(string $body): void
    {
        $request = new Request(body: $body);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('El cuerpo debe ser un objeto JSON');

        $request->json();
    }

    // ===== fromGlobals =====

    public function test_from_globals_lee_metodo_y_query(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'put';
        $_SERVER['REQUEST_URI'] = '/api/roles/7?x=1';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_GET = ['x' => '1'];

        $request = Request::fromGlobals();

        $this->assertSame('PUT', $request->method());
        $this->assertSame('/api/roles/7', $request->path());
        $this->assertSame('1', $request->query('x'));
    }

    public function test_from_globals_quita_el_prefijo_de_subdirectorio(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/proyecto/public/api/health';
        $_SERVER['SCRIPT_NAME'] = '/proyecto/public/index.php';
        $_GET = [];

        $request = Request::fromGlobals();

        $this->assertSame('/api/health', $request->path());
    }

    public function test_from_globals_quita_prefijo_de_public(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/public/api/health';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_GET = [];

        $request = Request::fromGlobals();

        $this->assertSame('/api/health', $request->path());
    }

    public function test_from_globals_lee_el_header_de_autorizacion(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/x';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer abc';
        $_GET = [];

        $request = Request::fromGlobals();

        $this->assertSame('Bearer abc', $request->header('Authorization'));
    }

    public function test_from_globals_usa_redirect_como_respaldo_del_token(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/x';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer respaldo';
        $_GET = [];

        $request = Request::fromGlobals();

        $this->assertSame('Bearer respaldo', $request->header('Authorization'));
    }

    public function test_from_globals_lee_post_y_files(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/x';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_GET = [];
        $_POST = ['campo' => 'valor'];
        $_FILES = ['imagen' => ['name' => 'a.jpg']];

        $request = Request::fromGlobals();

        $this->assertSame('valor', $request->post('campo'));
        $this->assertSame(['name' => 'a.jpg'], $request->file('imagen'));
    }

    protected function tearDown(): void
    {
        unset(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['SCRIPT_NAME'],
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );

        parent::tearDown();
    }
}
