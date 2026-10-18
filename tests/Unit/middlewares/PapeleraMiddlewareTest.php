<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Helpers\TokenProviderInterface;
use App\Middlewares\AutenticadorMiddleware;
use App\Middlewares\PapeleraMiddleware;
use App\Models\Rol;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PapeleraMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->limpiar();
    }

    protected function tearDown(): void
    {
        $this->limpiar();
        $this->establecerProvider(null);

        parent::tearDown();
    }

    private function limpiar(): void
    {
        unset(
            $_GET['solo_eliminados'],
            $_GET['incluir_eliminados'],
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );
    }

    private function establecerProvider(?object $usuario): void
    {
        // La via publica para configurar el provider.
        if ($usuario !== null) {
            $provider = $this->createMock(TokenProviderInterface::class);
            $provider->method('validate')->willReturn($usuario);

            AutenticadorMiddleware::configure($provider);
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token';

            return;
        }

        // No hay API publica para dejarlo en null: se usa reflexion.
        $propiedad = new \ReflectionProperty(
            AutenticadorMiddleware::class,
            'tokenProvider'
        );

        $propiedad->setValue(null, null);
    }

    public function test_no_pide_papelera_sin_flags(): void
    {
        $this->assertFalse(PapeleraMiddleware::pidePapelera());
    }

    public function test_pide_papelera_con_solo_eliminados(): void
    {
        $_GET['solo_eliminados'] = '1';

        $this->assertTrue(PapeleraMiddleware::pidePapelera());
    }

    public function test_pide_papelera_con_incluir_eliminados(): void
    {
        $_GET['incluir_eliminados'] = 'true';

        $this->assertTrue(PapeleraMiddleware::pidePapelera());
    }

    #[DataProvider('flagsFalsos')]
    public function test_flag_falso_no_pide_papelera(string $valor): void
    {
        $_GET['solo_eliminados'] = $valor;

        $this->assertFalse(PapeleraMiddleware::pidePapelera());
    }

    public static function flagsFalsos(): array
    {
        return [
            'cero' => ['0'],
            'false' => ['false'],
            'off' => ['off'],
            'no' => ['no'],
            'vacio' => [''],
        ];
    }

    public function test_verificar_sin_flag_devuelve_null_sin_autenticar(): void
    {
        $this->assertNull(
            PapeleraMiddleware::verificarSiPidePapelera()
        );
    }

    public function test_verificar_con_flag_falso_devuelve_null(): void
    {
        $_GET['solo_eliminados'] = 'false';

        $this->assertNull(
            PapeleraMiddleware::verificarSiPidePapelera()
        );
    }

    public function test_verificar_papelera_devuelve_al_admin(): void
    {
        $admin = (object) ['sub' => 1, 'rol_id' => Rol::ADMIN];

        $this->establecerProvider($admin);
        $_GET['solo_eliminados'] = '1';

        $this->assertSame(
            $admin,
            PapeleraMiddleware::verificarSiPidePapelera()
        );
    }

    public function test_verificar_papelera_lanza_si_no_es_admin(): void
    {
        $usuario = (object) ['sub' => 2, 'rol_id' => 1];

        $this->establecerProvider($usuario);
        $_GET['incluir_eliminados'] = '1';

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'Solo un administrador puede ver los registros eliminados'
        );

        PapeleraMiddleware::verificarSiPidePapelera();
    }

    public function test_verificar_papelera_sin_token_lanza_unauthorized(): void
    {
        $this->establecerProvider(
            (object) ['sub' => 1, 'rol_id' => Rol::ADMIN]
        );
        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );
        $_GET['solo_eliminados'] = '1';

        $this->expectException(UnauthorizedException::class);

        PapeleraMiddleware::verificarSiPidePapelera();
    }
}
