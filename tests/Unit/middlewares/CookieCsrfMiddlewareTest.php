<?php

declare(strict_types=1);

namespace Tests\Unit\middlewares;

use App\Exceptions\ForbiddenException;
use App\Http\Request;
use App\Middlewares\CookieCsrfMiddleware;
use PHPUnit\Framework\TestCase;

final class CookieCsrfMiddlewareTest extends TestCase
{
    public function test_permite_escritura_con_cookie_y_origen_autorizado(): void
    {
        $this->expectNotToPerformAssertions();

        CookieCsrfMiddleware::verificar(
            new Request(
                method: 'POST',
                headers: ['Origin' => 'https://frontend.example'],
                cookies: ['access_token' => 'jwt']
            ),
            ['https://frontend.example']
        );
    }

    public function test_rechaza_escritura_con_cookie_y_origen_no_autorizado(): void
    {
        $this->expectException(ForbiddenException::class);

        CookieCsrfMiddleware::verificar(
            new Request(
                method: 'POST',
                headers: ['Origin' => 'https://attacker.example'],
                cookies: ['refresh_token' => 'refresh']
            ),
            ['https://frontend.example']
        );
    }

    public function test_rechaza_escritura_con_cookie_y_sin_origen(): void
    {
        $this->expectException(ForbiddenException::class);

        CookieCsrfMiddleware::verificar(
            new Request(
                method: 'DELETE',
                cookies: ['access_token' => 'jwt']
            ),
            ['https://frontend.example']
        );
    }

    public function test_no_exige_origen_en_metodos_de_solo_lectura(): void
    {
        $this->expectNotToPerformAssertions();

        CookieCsrfMiddleware::verificar(
            new Request(
                method: 'GET',
                cookies: ['access_token' => 'jwt']
            ),
            []
        );
    }
}
