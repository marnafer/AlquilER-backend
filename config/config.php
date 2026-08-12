<?php
// 1. Configuración de Entorno
define('APP_ENV', 'development');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
}

// 2. Configuración de Seguridad (JWT)
// Intentamos leer de las variables de entorno, si no existe, usamos una por defecto (útil para desarrollo local)
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'clave_larga_y_segura_para_firmar_los_tokens_de_autenticacion_12345678');
define('JWT_EXPIRATION', getenv('JWT_EXP') ?: 3600);
define('JWT_ALGORITHM', 'HS256');