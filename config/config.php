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
    error_reporting(0);
    ini_set('display_errors', '0');
}

$requiredEnv = [
    'JWT_KEY',
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

define('JWT_KEY', $jwtKey);
define('JWT_EXPIRATION', $jwtExpiration);
define('JWT_ALGORITHM', 'HS256');