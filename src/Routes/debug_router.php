<?php

/**
 * Router de Debug
 */

require_once SRC_PATH . 'Controllers/Api/DebugController.php';


use App\Controllers\Api\DebugController;
// Defensa en profundidad: aunque este archivo sea incluido por alg?n otro
// camino, las rutas de debug solo responden en entorno de desarrollo.
if (($_ENV['APP_ENV'] ?? 'production') !== 'development') {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'error' => 'Not Found'
    ]);

    return true;
}

$controller = new DebugController();

$debugMethod = $_SERVER['REQUEST_METHOD'];
$debugPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '',
    PHP_URL_PATH
);

// Estad??sticas
if ($debugPath === '/api/debug/stats' && $debugMethod === 'GET') {
    $controller->stats();
    return true;
}

// Logs
if ($debugPath === '/api/debug/logs' && $debugMethod === 'GET') {
    $controller->logs();
    return true;
}

// Limpiar log
if ($debugPath === '/api/debug/clear-log' && $debugMethod === 'POST') {
    $controller->clearLog();
    return true;
}

// Test DB
if ($debugPath === '/api/debug/test-db' && $debugMethod === 'GET') {
    $controller->testDB();
    return true;
}

// PHP Info
if ($debugPath === '/api/debug/phpinfo' && $debugMethod === 'GET') {
    $controller->phpinfo();
    return true;
}

return false;
