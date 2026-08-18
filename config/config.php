<?php

// 1. Configuración de Entorno

define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
}

// 2. Configuración de Seguridad (JWT)

define('JWT_SECRET', $_ENV['JWT_SECRET']);
define('JWT_EXPIRATION', (int) ($_ENV['JWT_EXP'] ?? 3600));
define('JWT_ALGORITHM', 'HS256');