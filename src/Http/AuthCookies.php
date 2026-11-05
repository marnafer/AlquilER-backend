<?php

declare(strict_types=1);

namespace App\Http;

final class AuthCookies
{
    public const ACCESS_TOKEN = 'access_token';
    public const REFRESH_TOKEN = 'refresh_token';

    private const ACCESS_TOKEN_TTL = 900;
    private const REFRESH_TOKEN_TTL = 1296000;

    /**
     * Adjunta ambos tokens como cookies HttpOnly.
     *
     * @param array<string, mixed> $tokens
     */
    public static function attach(Response $response, array $tokens): Response
    {
        foreach ([self::ACCESS_TOKEN, self::REFRESH_TOKEN] as $name) {
            if (
                !isset($tokens[$name])
                || !is_string($tokens[$name])
                || $tokens[$name] === ''
            ) {
                throw new \InvalidArgumentException(
                    "Falta un token válido para la cookie {$name}"
                );
            }
        }

        return $response
            ->withCookie(
                self::ACCESS_TOKEN,
                $tokens[self::ACCESS_TOKEN],
                self::options('/', self::ACCESS_TOKEN_TTL)
            )
            ->withCookie(
                self::REFRESH_TOKEN,
                $tokens[self::REFRESH_TOKEN],
                self::options(self::authPath(), self::REFRESH_TOKEN_TTL)
            );
    }

    public static function clear(Response $response): Response
    {
        return $response
            ->withCookie(
                self::ACCESS_TOKEN,
                '',
                self::options('/', 0)
            )
            ->withCookie(
                self::REFRESH_TOKEN,
                '',
                self::options(self::authPath(), 0)
            );
    }

    private static function authPath(): string
    {
        $basePath = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        return $basePath . '/api/autenticador';
    }

    /**
     * @return array{expires: int, path: string, secure: bool, httponly: bool, samesite: string}
     */
    private static function options(string $path, int $ttl): array
    {
        $secure = defined('AUTH_COOKIE_SECURE')
            ? AUTH_COOKIE_SECURE
            : (defined('APP_ENV') && APP_ENV !== 'development');
        $sameSite = defined('AUTH_COOKIE_SAMESITE')
            ? AUTH_COOKIE_SAMESITE
            : 'Lax';

        return [
            'expires' => $ttl === 0 ? 1 : time() + $ttl,
            'path' => $path,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ];
    }
}
