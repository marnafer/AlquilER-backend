<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Helpers\TokenProviderInterface;
use App\Middlewares\AutenticadorMiddleware;
use App\Models\Rol;
use PHPUnit\Framework\TestCase;

final class AutenticadorMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->limpiarAuthHeader();
    }

    protected function tearDown(): void
    {
        $this->limpiarAuthHeader();
        $this->establecerProvider(null);

        parent::tearDown();
    }

    private function limpiarAuthHeader(): void
    {
        unset(
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        );
    }

    private function establecerProvider(
        ?TokenProviderInterface $provider
    ): void {
        // La via publica para configurar el provider.
        if ($provider !== null) {
            AutenticadorMiddleware::configure($provider);

            return;
        }

        // No hay API publica para dejarlo en null: se usa reflexion.
        $propiedad = new \ReflectionProperty(
            AutenticadorMiddleware::class,
            'tokenProvider'
        );

        $propiedad->setValue(null, null);
    }

    private function providerQueDevuelve(
        ?object $usuario
    ): TokenProviderInterface {
        $provider = $this->createMock(TokenProviderInterface::class);
        $provider->method('validate')->willReturn($usuario);

        return $provider;
    }

    public function test_verificar_devuelve_el_usuario_con_token_valido(): void
    {
        $usuario = (object) ['sub' => 5, 'rol_id' => 1];

        $this->establecerProvider($this->providerQueDevuelve($usuario));
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-valido';

        $this->assertSame(
            $usuario,
            AutenticadorMiddleware::verificar()
        );
    }

    public function test_verificar_lanza_si_falta_el_header(): void
    {
        $this->establecerProvider($this->providerQueDevuelve(null));

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Token requerido');

        AutenticadorMiddleware::verificar();
    }

    public function test_verificar_lanza_si_el_formato_no_es_bearer(): void
    {
        $this->establecerProvider($this->providerQueDevuelve(null));
        $_SERVER['HTTP_AUTHORIZATION'] = 'Token token-valido';

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Formato de token inválido');

        AutenticadorMiddleware::verificar();
    }

    public function test_verificar_lanza_si_el_proveedor_no_fue_configurado(): void
    {
        $this->establecerProvider(null);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-valido';

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'El proveedor de tokens no fue configurado'
        );

        AutenticadorMiddleware::verificar();
    }

    public function test_verificar_lanza_si_el_token_es_invalido(): void
    {
        $this->establecerProvider($this->providerQueDevuelve(null));
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-vencido';

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Token inválido o expirado');

        AutenticadorMiddleware::verificar();
    }

    public function test_verificar_usa_redirect_http_authorization(): void
    {
        $usuario = (object) ['sub' => 7, 'rol_id' => 1];

        $this->establecerProvider($this->providerQueDevuelve($usuario));
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer token-redirect';

        $this->assertSame(
            $usuario,
            AutenticadorMiddleware::verificar()
        );
    }

    public function test_verificar_prioriza_http_authorization(): void
    {
        $usuario = (object) ['sub' => 9, 'rol_id' => 1];

        $provider = $this->createMock(TokenProviderInterface::class);
        $provider->expects($this->once())
            ->method('validate')
            ->with('token-http')
            ->willReturn($usuario);

        $this->establecerProvider($provider);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-http';
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer token-redirect';

        $this->assertSame(
            $usuario,
            AutenticadorMiddleware::verificar()
        );
    }

    public function test_solo_admin_devuelve_al_administrador(): void
    {
        $admin = (object) ['sub' => 1, 'rol_id' => Rol::ADMIN];

        $this->establecerProvider($this->providerQueDevuelve($admin));
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-admin';

        $this->assertSame(
            $admin,
            AutenticadorMiddleware::soloAdmin()
        );
    }

    public function test_solo_admin_lanza_si_no_es_administrador(): void
    {
        $usuario = (object) ['sub' => 2, 'rol_id' => 1];

        $this->establecerProvider($this->providerQueDevuelve($usuario));
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token-usuario';

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Solo administradores');

        AutenticadorMiddleware::soloAdmin();
    }
}
