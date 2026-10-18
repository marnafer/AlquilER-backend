<?php

declare(strict_types=1);

namespace Tests\Unit\Routes;

use App\Exceptions\MethodNotAllowedException;
use App\Exceptions\NotFoundException;
use App\Routes\Router;
use PHPUnit\Framework\TestCase;

final class RouterControladorFalso
{
    public static array $llamadas = [];

    public static function reset(): void
    {
        self::$llamadas = [];
    }

    public function index(): void
    {
        self::registrar('index', func_get_args());
    }

    public function show(string $id): void
    {
        self::registrar('show', func_get_args());
    }

    public function multi(string $a, string $b): void
    {
        self::registrar('multi', func_get_args());
    }

    public function me(): void
    {
        self::registrar('me', func_get_args());
    }

    public function store(): void
    {
        self::registrar('store', func_get_args());
    }

    public function conRequest($request): void
    {
        self::registrar('conRequest', func_get_args());
    }

    public function conRequestYParam($request, string $id): void
    {
        self::registrar('conRequestYParam', func_get_args());
    }

    private static function registrar(string $metodo, array $args): void
    {
        self::$llamadas[] = ['metodo' => $metodo, 'args' => $args];
    }
}

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = new Router();
        RouterControladorFalso::reset();
    }

    public function test_despacha_una_ruta_exacta_con_instancia_de_controlador(): void
    {
        $this->router->get('/api/x', [new RouterControladorFalso(), 'index']);

        $this->router->dispatch('GET', '/api/x');

        $this->assertSame(
            [['metodo' => 'index', 'args' => []]],
            RouterControladorFalso::$llamadas
        );
    }

    public function test_despacha_una_ruta_exacta_con_nombre_de_clase(): void
    {
        $this->router->get('/api/x', [RouterControladorFalso::class, 'index']);

        $this->router->dispatch('GET', '/api/x');

        $this->assertSame('index', RouterControladorFalso::$llamadas[0]['metodo']);
    }

    public function test_despacha_una_ruta_dinamica_con_un_parametro(): void
    {
        $this->router->get('/api/usuarios/{id}', [new RouterControladorFalso(), 'show']);

        $this->router->dispatch('GET', '/api/usuarios/42');

        $this->assertSame('show', RouterControladorFalso::$llamadas[0]['metodo']);
        $this->assertSame(['42'], RouterControladorFalso::$llamadas[0]['args']);
    }

    public function test_despacha_una_ruta_dinamica_con_varios_parametros(): void
    {
        $this->router->get('/api/a/{x}/b/{y}', [new RouterControladorFalso(), 'multi']);

        $this->router->dispatch('GET', '/api/a/uno/b/dos');

        $this->assertSame(['multi', ['uno', 'dos']], [
            RouterControladorFalso::$llamadas[0]['metodo'],
            RouterControladorFalso::$llamadas[0]['args'],
        ]);
    }

    public function test_la_ruta_exacta_tiene_prioridad_sobre_la_dinamica(): void
    {
        $this->router->get('/api/usuarios/{id}', [new RouterControladorFalso(), 'show']);
        $this->router->get('/api/usuarios/me', [new RouterControladorFalso(), 'me']);

        $this->router->dispatch('GET', '/api/usuarios/me');

        $this->assertSame('me', RouterControladorFalso::$llamadas[0]['metodo']);
        $this->assertCount(1, RouterControladorFalso::$llamadas);
    }

    public function test_la_ruta_dinamica_no_cruza_segmentos(): void
    {
        $this->router->get('/api/usuarios/{id}', [new RouterControladorFalso(), 'show']);

        $this->expectException(NotFoundException::class);

        $this->router->dispatch('GET', '/api/usuarios/1/2');
    }

    public function test_la_ruta_dinamica_requiere_al_menos_un_caracter(): void
    {
        $this->router->get('/api/usuarios/{id}', [new RouterControladorFalso(), 'show']);

        $this->expectException(NotFoundException::class);

        $this->router->dispatch('GET', '/api/usuarios/');
    }

    public function test_acepta_parametros_con_guion_bajo_y_mayusculas(): void
    {
        $this->router->get('/api/usuarios/{usuario_ID}', [new RouterControladorFalso(), 'show']);

        $this->router->dispatch('GET', '/api/usuarios/7');

        $this->assertSame(['7'], RouterControladorFalso::$llamadas[0]['args']);
    }

    public function test_inyecta_request_como_primer_argumento_si_la_accion_lo_declara(): void
    {
        $this->router->get('/api/x', [new RouterControladorFalso(), 'conRequest']);

        $this->router->dispatch('GET', '/api/x');

        $this->assertSame(
            [['metodo' => 'conRequest', 'args' => [null]]],
            RouterControladorFalso::$llamadas
        );
    }

    public function test_inyecta_request_en_una_ruta_dinamica_preservando_los_parametros(): void
    {
        $this->router->get('/api/usuarios/{id}', [new RouterControladorFalso(), 'conRequestYParam']);

        $this->router->dispatch('GET', '/api/usuarios/7');

        $this->assertSame('conRequestYParam', RouterControladorFalso::$llamadas[0]['metodo']);
        $this->assertSame([null, '7'], RouterControladorFalso::$llamadas[0]['args']);
    }

    public function test_no_inyecta_request_cuando_la_accion_no_lo_declara(): void
    {
        $this->router->get('/api/x', [new RouterControladorFalso(), 'index']);

        $this->router->dispatch('GET', '/api/x');

        $this->assertSame([], RouterControladorFalso::$llamadas[0]['args']);
    }

    public function test_registrar_la_misma_ruta_sobrescribe_el_handler(): void
    {
        $this->router->get('/api/x', [new RouterControladorFalso(), 'index']);
        $this->router->get('/api/x', [new RouterControladorFalso(), 'me']);

        $this->router->dispatch('GET', '/api/x');

        $this->assertSame('me', RouterControladorFalso::$llamadas[0]['metodo']);
        $this->assertCount(1, RouterControladorFalso::$llamadas);
    }

    public function test_lanza_method_not_allowed_cuando_la_ruta_existe_con_otro_metodo(): void
    {
        $this->router->get('/api/x', [new RouterControladorFalso(), 'index']);

        try {
            $this->router->dispatch('POST', '/api/x');
            $this->fail('Se esperaba MethodNotAllowedException');
        } catch (MethodNotAllowedException $e) {
            $this->assertSame(405, $e->getCode());
        }

        $this->assertSame([], RouterControladorFalso::$llamadas);
    }

    public function test_lanza_method_not_allowed_para_una_ruta_dinamica_con_otro_metodo(): void
    {
        $this->router->get('/api/usuarios/{id}', [new RouterControladorFalso(), 'show']);

        $this->expectException(MethodNotAllowedException::class);

        $this->router->dispatch('DELETE', '/api/usuarios/5');
    }

    public function test_lanza_not_found_para_una_ruta_desconocida(): void
    {
        $this->router->get('/api/x', [new RouterControladorFalso(), 'index']);

        try {
            $this->router->dispatch('GET', '/api/desconocida');
            $this->fail('Se esperaba NotFoundException');
        } catch (NotFoundException $e) {
            $this->assertSame(404, $e->getCode());
        }

        $this->assertSame([], RouterControladorFalso::$llamadas);
    }

    public function test_lanza_not_found_con_path_vacio(): void
    {
        $this->router->get('/api/x', [new RouterControladorFalso(), 'index']);

        $this->expectException(NotFoundException::class);

        $this->router->dispatch('GET', '');
    }

    public function test_no_ejecuta_el_handler_cuando_la_ruta_no_coincide(): void
    {
        $this->router->get('/api/x', [new RouterControladorFalso(), 'index']);

        try {
            $this->router->dispatch('GET', '/api/y');
        } catch (NotFoundException) {
            // esperado
        }

        $this->assertSame([], RouterControladorFalso::$llamadas);
    }

    public function test_registra_y_despacha_los_metodos_put_patch_y_delete(): void
    {
        $this->router->put('/api/x', [new RouterControladorFalso(), 'store']);
        $this->router->patch('/api/x', [new RouterControladorFalso(), 'store']);
        $this->router->delete('/api/x', [new RouterControladorFalso(), 'store']);

        $this->router->dispatch('PUT', '/api/x');
        $this->router->dispatch('PATCH', '/api/x');
        $this->router->dispatch('DELETE', '/api/x');

        $this->assertCount(3, RouterControladorFalso::$llamadas);
        $this->assertSame('store', RouterControladorFalso::$llamadas[0]['metodo']);
    }

    public function test_el_metodo_se_distingue_por_mayusculas(): void
    {
        $this->router->get('/api/x', [new RouterControladorFalso(), 'index']);

        $this->expectException(MethodNotAllowedException::class);

        $this->router->dispatch('get', '/api/x');
    }

    public function test_multiples_rutas_dinamicas_se_evaluan_en_orden_de_registro(): void
    {
        $this->router->get('/api/{a}', [new RouterControladorFalso(), 'show']);
        $this->router->get('/api/{otro}', [new RouterControladorFalso(), 'me']);

        $this->router->dispatch('GET', '/api/valor');

        $this->assertSame('show', RouterControladorFalso::$llamadas[0]['metodo']);
        $this->assertSame(['valor'], RouterControladorFalso::$llamadas[0]['args']);
    }
}
