<?php

declare(strict_types=1);

define('APP_ENV', trim((string) ($_ENV['APP_ENV'] ?? 'development')));

$debug = filter_var(
    $_ENV['APP_DEBUG'] ?? false,
    FILTER_VALIDATE_BOOLEAN
);

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', $debug ? '1' : '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

$requiredEnv = [
    'DB_HOST',
    'DB_DATABASE',
    'DB_USERNAME',
    'JWT_KEY',
    'APP_URL',
];

foreach ($requiredEnv as $variable) {
    if (
        !isset($_ENV[$variable])
        || trim((string) $_ENV[$variable]) === ''
    ) {
        throw new RuntimeException(
            "Falta la variable de entorno obligatoria: {$variable}"
        );
    }
}

$jwtKey = trim((string) $_ENV['JWT_KEY']);

if (strlen($jwtKey) < 32) {
    throw new RuntimeException(
        'JWT_KEY debe tener al menos 32 caracteres'
    );
}

$jwtExpiration = filter_var(
    $_ENV['JWT_EXP'] ?? 3600,
    FILTER_VALIDATE_INT
);

if ($jwtExpiration === false || $jwtExpiration <= 0) {
    throw new RuntimeException(
        'JWT_EXP debe ser un entero positivo'
    );
}

if (APP_ENV === 'production' && $debug) {
    throw new RuntimeException(
        'APP_DEBUG no puede estar activo en producción'
    );
}

define('APP_URL', rtrim((string) $_ENV['APP_URL'], '/'));
define('JWT_KEY', $jwtKey);
define('JWT_EXPIRATION', $jwtExpiration);
define('JWT_ALGORITHM', 'HS256');

$cookieSecure = filter_var(
    $_ENV['AUTH_COOKIE_SECURE']
        ?? (APP_ENV !== 'development' || str_starts_with(APP_URL, 'https://')),
    FILTER_VALIDATE_BOOLEAN,
    FILTER_NULL_ON_FAILURE
);

if ($cookieSecure === null) {
    throw new RuntimeException(
        'AUTH_COOKIE_SECURE debe ser true o false'
    );
}

if (APP_ENV === 'production' && !$cookieSecure) {
    throw new RuntimeException(
        'AUTH_COOKIE_SECURE debe ser true en producción'
    );
}

$cookieSameSite = ucfirst(strtolower(trim(
    (string) ($_ENV['AUTH_COOKIE_SAMESITE'] ?? 'Lax')
)));

if (!in_array($cookieSameSite, ['Lax', 'Strict', 'None'], true)) {
    throw new RuntimeException(
        'AUTH_COOKIE_SAMESITE debe ser Lax, Strict o None'
    );
}

if ($cookieSameSite === 'None' && !$cookieSecure) {
    throw new RuntimeException(
        'AUTH_COOKIE_SECURE debe ser true cuando AUTH_COOKIE_SAMESITE es None'
    );
}

define('AUTH_COOKIE_SECURE', $cookieSecure);
define('AUTH_COOKIE_SAMESITE', $cookieSameSite);