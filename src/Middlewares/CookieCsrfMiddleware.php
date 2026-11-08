<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Exceptions\ForbiddenException;
use App\Http\AuthCookies;
use App\Http\Request;

final class CookieCsrfMiddleware
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Exige un Origin permitido para las operaciones que envían cookies de auth.
     *
     * @param list<string> $allowedOrigins
     */
    public static function verificar(Request $request, array $allowedOrigins): void
    {
        if (
            in_array($request->method(), self::SAFE_METHODS, true)
            || (
                $request->cookie(AuthCookies::ACCESS_TOKEN) === null
                && $request->cookie(AuthCookies::REFRESH_TOKEN) === null
            )
        ) {
            return;
        }

        $origin = $request->header('Origin');

        if ($origin === null || !in_array($origin, $allowedOrigins, true)) {
            throw new ForbiddenException(
                'Origen no permitido para una solicitud autenticada'
            );
        }
    }
}
