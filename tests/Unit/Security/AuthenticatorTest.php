<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Helpers\TokenProviderInterface;
use App\Http\Request;
use App\Models\Rol;
use App\Security\Authenticator;
use PHPUnit\Framework\TestCase;

final class AuthenticatorTest extends TestCase
{
    private function providerQueDevuelve(mixed $resultado): TokenProviderInterface
    {
        $provider = $this->createMock(TokenProviderInterface::class);
        $provider->method('validate')->willReturn($resultado);

        return $provider;
    }

    private function conAuthHeader(string $header): Request
    {
        return new Request(headers: ['Authorization' => $header]);
    }

    // ===== verificar =====

    public function test_verificar_devuelve_al_usuario_si_el_token_es_valido(): void
    {
        $usuario = (object) ['sub' => 7, 'rol_id' => Rol::ADMIN];
        $auth = new Authenticator($this->providerQueDevuelve($usuario));

        $resultado = $auth->verificar($this->conAuthHeader('Bearer token-bueno'));

        $this->assertSame($usuario, $resultado);
    }

    public function test_verificar_lanza_si_no_hay_header_de_autorizacion(): void
    {
        $auth = new Authenticator($this->providerQueDevuelve(null));

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Token requerido');

        $auth->verificar(new Request());
    }

    public function test_verificar_lanza_si_el_formato_no_es_bearer(): void
    {
        $auth = new Authenticator($this->providerQueDevuelve(null));

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Formato de token inválido');

        $auth->verificar($this->conAuthHeader('Basic dXN1YXJpbw=='));
    }

    public function test_verificar_lanza_si_el_token_esta_vacio(): void
    {
        $auth = new Authenticator($this->providerQueDevuelve(null));

        // "Bearer" sin nada despues no cumple el prefijo "Bearer ".
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Formato de token inválido');

        $auth->verificar($this->conAuthHeader('Bearer'));
    }

    public function test_verificar_lanza_si_el_provider_rechaza_el_token(): void
    {
        $auth = new Authenticator($this->providerQueDevuelve(null));

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Token inválido o expirado');

        $auth->verificar($this->conAuthHeader('Bearer token-vencido'));
    }

    public function test_verificar_lee_el_header_de_la_peticion(): void
    {
        $usuario = (object) ['sub' => 1, 'rol_id' => Rol::USUARIO];
        $auth = new Authenticator($this->providerQueDevuelve($usuario));

        // Case-insensitive en el nombre del header.
        $resultado = $auth->verificar(
            new Request(headers: ['authorization' => 'Bearer abc'])
        );

        $this->assertSame($usuario, $resultado);
    }

    // ===== soloAdmin =====

    public function test_solo_admin_devuelve_al_admin(): void
    {
        $admin = (object) ['sub' => 1, 'rol_id' => Rol::ADMIN];
        $auth = new Authenticator($this->providerQueDevuelve($admin));

        $this->assertSame(
            $admin,
            $auth->soloAdmin($this->conAuthHeader('Bearer admin'))
        );
    }

    public function test_solo_admin_lanza_si_no_es_administrador(): void
    {
        $usuario = (object) ['sub' => 2, 'rol_id' => Rol::USUARIO];
        $auth = new Authenticator($this->providerQueDevuelve($usuario));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Solo administradores');

        $auth->soloAdmin($this->conAuthHeader('Bearer user'));
    }

    public function test_solo_admin_propaga_unauthorized_si_no_hay_token(): void
    {
        $auth = new Authenticator($this->providerQueDevuelve(null));

        $this->expectException(UnauthorizedException::class);

        $auth->soloAdmin(new Request());
    }

    // ===== papelera =====

    public function test_no_pide_papelera_si_no_hay_flags(): void
    {
        $auth = new Authenticator($this->providerQueDevuelve(null));

        $this->assertFalse($auth->pidePapelera(new Request()));
        $this->assertNull(
            $auth->verificarSiPidePapelera(new Request())
        );
    }

    public function test_pide_papelera_detecta_solo_eliminados(): void
    {
        $auth = new Authenticator($this->providerQueDevuelve(null));

        $this->assertTrue(
            $auth->pidePapelera(
                new Request(query: ['solo_eliminados' => '1'])
            )
        );
    }

    public function test_pide_papelera_detecta_incluir_eliminados(): void
    {
        $auth = new Authenticator($this->providerQueDevuelve(null));

        $this->assertTrue(
            $auth->pidePapelera(
                new Request(query: ['incluir_eliminados' => 'true'])
            )
        );
    }

    public function test_pide_papelera_ignora_valores_falsy(): void
    {
        $auth = new Authenticator($this->providerQueDevuelve(null));

        $this->assertFalse(
            $auth->pidePapelera(
                new Request(query: ['solo_eliminados' => '0'])
            )
        );
        $this->assertFalse(
            $auth->pidePapelera(
                new Request(query: ['solo_eliminados' => 'false'])
            )
        );
    }

    public function test_verificar_papelera_devuelve_al_admin(): void
    {
        $admin = (object) ['sub' => 9, 'rol_id' => Rol::ADMIN];
        $auth = new Authenticator($this->providerQueDevuelve($admin));

        $resultado = $auth->verificarSiPidePapelera(
            $this->conAuthHeader('Bearer admin')
                ->withQuery(['solo_eliminados' => '1'])
        );

        $this->assertSame($admin, $resultado);
    }

    public function test_verificar_papelera_lanza_si_no_es_admin(): void
    {
        $usuario = (object) ['sub' => 9, 'rol_id' => Rol::USUARIO];
        $auth = new Authenticator($this->providerQueDevuelve($usuario));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage(
            'Solo un administrador puede ver los registros eliminados'
        );

        $auth->verificarSiPidePapelera(
            $this->conAuthHeader('Bearer user')
                ->withQuery(['solo_eliminados' => '1'])
        );
    }

    public function test_verificar_papelera_sin_token_lanza_unauthorized(): void
    {
        $auth = new Authenticator($this->providerQueDevuelve(null));

        $this->expectException(UnauthorizedException::class);

        $auth->verificarSiPidePapelera(
            new Request(query: ['solo_eliminados' => '1'])
        );
    }
}
