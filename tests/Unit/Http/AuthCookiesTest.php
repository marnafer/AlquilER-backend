<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\AuthCookies;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

final class AuthCookiesTest extends TestCase
{
    public function test_attach_lanza_excepcion_si_falta_access_token(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AuthCookies::attach(
            Response::success([]),
            ['refresh_token' => 'refresh']
        );
    }

    public function test_attach_lanza_excepcion_si_falta_refresh_token(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AuthCookies::attach(
            Response::success([]),
            ['access_token' => 'access']
        );
    }

    public function test_attach_lanza_excepcion_si_token_esta_vacio(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AuthCookies::attach(
            Response::success([]),
            ['access_token' => '', 'refresh_token' => 'refresh']
        );
    }

    public function test_attach_lanza_excepcion_si_token_no_es_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AuthCookies::attach(
            Response::success([]),
            ['access_token' => 123, 'refresh_token' => 'refresh']
        );
    }
}
