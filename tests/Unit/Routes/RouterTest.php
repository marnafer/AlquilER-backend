<?php

declare(strict_types=1);

namespace Tests\Unit\Routes;

use App\Exceptions\MethodNotAllowedException;
use App\Exceptions\NotFoundException;
use App\Http\Request;
use App\Http\Response;
use App\Routes\Router;
use PHPUnit\Framework\TestCase;

final class ControladorFalso
{
    public static array $llamadas = [];

    public static function reset(): void
    {
        self::$llamadas = [];
    }

    public function index(Request $request): Response
    {
        return self::registrar('index');
    }

    public function show(Request $request, string $id): Response
    {
        return self::registrar('show', $id);
    }

    public function multi(Request $request, string $a, string $b): Response
    {
        return self::registrar('multi', $a, $b);
    }

    public function me(Request $request): Response
    {
        return self::registrar('me');
    }

    public function store(Request $request): Response
    {
        return self::registrar('store');
    }

    private static function registrar(string $metodo, string ...$args): Response
    {
        self::$llamadas[] = ['metodo' => $metodo, 'args' => $args];

        return Response::success(['metodo' => $metodo]);
    }
}

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = new Router();
        ControladorFalso::reset();
    }

    private function get(string $path): Request
    {
        return new Request(method: 'GET', path: $path);
    }

    public function test_despacha_una_ruta_exacta_con_instancia_de_controlador(): void
    {
        $this->router->get('/api/x', [new ControladorFalso(), 'index']);

        $response = $this->router->dispatch($this->get('/api/x'));

        $this->assertSame(
            [['metodo' => 'index', 'args' => []]],
            ControladorFalso::$llamadas
        );
        $this->assertSame(200, $response->status());
        $this->assertSame('index', $response->payload()['data']['metodo']);
    }

    public function test_despacha_una_ruta_exacta_con_nombre_de_clase(): void
    {
        $this->router->get('/api/x', [ControladorFalso::class, 'index']);

        $this->router->dispatch($this->get('/api/x'));

        $this->assertSame('index', ControladorFalso::$llamadas[0]['metodo']);
    }

    public function test_despacha_una_ruta_dinamica_con_un_parametro(): void
    {
        $this->router->get('/api/usuarios/{id}', [new ControladorFalso(), 'show']);

        $this->router->dispatch($this->get('/api/usuarios/42'));

        $this->assertSame('show', ControladorFalso::$llamadas[0]['metodo']);
        $this->assertSame(['42'], ControladorFalso::$llamadas[0]['args']);
    }

    public function test_despacha_una_ruta_dinamica_con_varios_parametros(): void
    {
        $this->router->get('/api/a/{x}/b/{y}', [new ControladorFalso(), 'multi']);

        $this->router->dispatch($this->get('/api/a/uno/b/dos'));

        $this->assertSame(
            ['multi', ['uno', 'dos']],
            [
                ControladorFalso::$llamadas[0]['metodo'],
                ControladorFalso::$llamadas[0]['args'],
            ]
        );
    }

    public function test_la_ruta_exacta_tiene_prioridad_sobre_la_dinamica(): void
    {
        $this->router->get('/api/usuarios/{id}', [new ControladorFalso(), 'show']);
        $this->router->get('/api/usuarios/me', [new ControladorFalso(), 'me']);

        $this->router->dispatch($this->get('/api/usuarios/me'));

        $this->assertSame('me', ControladorFalso::$llamadas[0]['metodo']);
        $this->assertCount(1, ControladorFalso::$llamadas);
    }

    public function test_la_ruta_dinamica_no_cruza_segmentos(): void
    {
        $this->router->get('/api/usuarios/{id}', [new ControladorFalso(), 'show']);

        $this->expectException(NotFoundException::class);

        $this->router->dispatch($this->get('/api/usuarios/1/2'));
    }

    public function test_la_ruta_dinamica_requiere_al_menos_un_caracter(): void
    {
        $this->router->get('/api/usuarios/{id}', [new ControladorFalso(), 'show']);

        $this->expectException(NotFoundException::class);

        $this->router->dispatch($this->get('/api/usuarios/'));
    }

    public function test_acepta_parametros_con_guion_bajo_y_mayusculas(): void
    {
        $this->router->get('/api/usuarios/{usuario_ID}', [new ControladorFalso(), 'show']);

        $this->router->dispatch($this->get('/api/usuarios/7'));

        $this->assertSame(['7'], ControladorFalso::$llamadas[0]['args']);
    }

    public function test_inyecta_request_como_primer_argumento(): void
    {
        $this->router->get('/api/x', [new ControladorFalso(), 'index']);

        $response = $this->router->dispatch($this->get('/api/x'));

        $this->assertSame(200, $response->status());
    }

    public function test_registrar_la_misma_ruta_sobrescribe_el_handler(): void
    {
        $this->router->get('/api/x', [new ControladorFalso(), 'index']);
        $this->router->get('/api/x', [new ControladorFalso(), 'me']);

        $this->router->dispatch($this->get('/api/x'));

        $this->assertSame('me', ControladorFalso::$llamadas[0]['metodo']);
        $this->assertCount(1, ControladorFalso::$llamadas);
    }

    public function test_lanza_method_not_allowed_cuando_la_ruta_existe_con_otro_metodo(): void
    {
        $this->router->get('/api/x', [new ControladorFalso(), 'index']);

        try {
            $this->router->dispatch(new Request(method: 'POST', path: '/api/x'));
            $this->fail('Se esperaba MethodNotAllowedException');
        } catch (MethodNotAllowedException $e) {
            $this->assertSame(405, $e->getCode());
        }

        $this->assertSame([], ControladorFalso::$llamadas);
    }

    public function test_lanza_method_not_allowed_para_una_ruta_dinamica_con_otro_metodo(): void
    {
        $this->router->get('/api/usuarios/{id}', [new ControladorFalso(), 'show']);

        $this->expectException(MethodNotAllowedException::class);

        $this->router->dispatch(new Request(method: 'DELETE', path: '/api/usuarios/5'));
    }

    public function test_lanza_not_found_para_una_ruta_desconocida(): void
    {
        $this->router->get('/api/x', [new ControladorFalso(), 'index']);

        try {
            $this->router->dispatch($this->get('/api/desconocida'));
            $this->fail('Se esperaba NotFoundException');
        } catch (NotFoundException $e) {
            $this->assertSame(404, $e->getCode());
        }

        $this->assertSame([], ControladorFalso::$llamadas);
    }

    public function test_lanza_not_found_con_path_vacio(): void
    {
        $this->router->get('/api/x', [new ControladorFalso(), 'index']);

        $this->expectException(NotFoundException::class);

        $this->router->dispatch($this->get(''));
    }

    public function test_no_ejecuta_el_handler_cuando_la_ruta_no_coincide(): void
    {
        $this->router->get('/api/x', [new ControladorFalso(), 'index']);

        try {
            $this->router->dispatch($this->get('/api/y'));
        } catch (NotFoundException) {
            // esperado
        }

        $this->assertSame([], ControladorFalso::$llamadas);
    }

    public function test_registra_y_despacha_los_metodos_put_patch_y_delete(): void
    {
        $this->router->put('/api/x', [new ControladorFalso(), 'store']);
        $this->router->patch('/api/x', [new ControladorFalso(), 'store']);
        $this->router->delete('/api/x', [new ControladorFalso(), 'store']);

        $this->router->dispatch(new Request(method: 'PUT', path: '/api/x'));
        $this->router->dispatch(new Request(method: 'PATCH', path: '/api/x'));
        $this->router->dispatch(new Request(method: 'DELETE', path: '/api/x'));

        $this->assertCount(3, ControladorFalso::$llamadas);
        $this->assertSame('store', ControladorFalso::$llamadas[0]['metodo']);
    }

    public function test_el_metodo_se_distingue_por_mayusculas(): void
    {
        $this->router->get('/api/x', [new ControladorFalso(), 'index']);

        $this->expectException(MethodNotAllowedException::class);

        $this->router->dispatch(new Request(method: 'get', path: '/api/x'));
    }

    public function test_multiples_rutas_dinamicas_se_evaluan_en_orden_de_registro(): void
    {
        $this->router->get('/api/{a}', [new ControladorFalso(), 'show']);
        $this->router->get('/api/{otro}', [new ControladorFalso(), 'me']);

        $this->router->dispatch($this->get('/api/valor'));

        $this->assertSame('show', ControladorFalso::$llamadas[0]['metodo']);
        $this->assertSame(['valor'], ControladorFalso::$llamadas[0]['args']);
    }

    public function test_post_tambien_despacha(): void
    {
        $this->router->post('/api/x', [new ControladorFalso(), 'store']);

        $response = $this->router->dispatch(new Request(method: 'POST', path: '/api/x'));

        $this->assertSame('store', ControladorFalso::$llamadas[0]['metodo']);
        $this->assertSame(200, $response->status());
    }

    public function test_matched_route_devuelve_la_plantilla_de_una_ruta_exacta(): void
    {
        $this->router->get('/api/usuarios/me', [new ControladorFalso(), 'me']);

        $this->router->dispatch($this->get('/api/usuarios/me'));

        $this->assertSame(
            '/api/usuarios/me',
            $this->router->matchedRoute()
        );
    }

    public function test_matched_route_devuelve_la_plantilla_y_no_el_id_dinamico(): void
    {
        $this->router->get(
            '/api/usuarios/{id}',
            [new ControladorFalso(), 'show']
        );

        $this->router->dispatch($this->get('/api/usuarios/42'));

        $this->assertSame(
            '/api/usuarios/{id}',
            $this->router->matchedRoute()
        );
    }

    public function test_matched_route_es_null_si_la_ruta_no_existe(): void
    {
        $this->router->get(
            '/api/usuarios/{id}',
            [new ControladorFalso(), 'show']
        );

        $this->router->dispatch($this->get('/api/usuarios/42'));
        $this->assertSame('/api/usuarios/{id}', $this->router->matchedRoute());

        try {
            $this->router->dispatch($this->get('/api/desconocida'));
            $this->fail('Se esperaba NotFoundException');
        } catch (NotFoundException) {
            $this->assertNull($this->router->matchedRoute());
        }
    }

    public function test_matched_route_se_conserva_para_una_respuesta_405(): void
    {
        $this->router->get(
            '/api/usuarios/{id}',
            [new ControladorFalso(), 'show']
        );

        try {
            $this->router->dispatch(
                new Request(method: 'DELETE', path: '/api/usuarios/42')
            );
            $this->fail('Se esperaba MethodNotAllowedException');
        } catch (MethodNotAllowedException $exception) {
            $this->assertSame(405, $exception->getCode());
            $this->assertSame(
                '/api/usuarios/{id}',
                $this->router->matchedRoute()
            );
        }
    }
}
