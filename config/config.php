<?php

// ============================================
// 1. Configuración de entorno
// ============================================

define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}


// ============================================
// 2. Validación de variables obligatorias
// ============================================

$requiredEnv = [
    'JWT_KEY'
];

foreach ($requiredEnv as $variable) {
    if (!isset($_ENV[$variable]) || trim((string) $_ENV[$variable]) === '') {
        throw new RuntimeException(
            "Falta la variable de entorno obligatoria: {$variable}"
        );
    }
}


// ============================================
// 3. Configuración de seguridad (JWT)
// ============================================

define('JWT_KEY', $_ENV['JWT_KEY']);
define('JWT_EXPIRATION', (int) ($_ENV['JWT_EXP'] ?? 3600));
define('JWT_ALGORITHM', 'HS256');