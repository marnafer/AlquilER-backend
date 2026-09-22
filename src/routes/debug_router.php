<?php
/**
 * Router de Debug
 */

require_once SRC_PATH . 'controllers/Api/DebugController.php';

use App\Controllers\DebugController;

// Defensa en profundidad: aunque este archivo sea incluido por algún otro
// camino, las rutas de debug solo responden en entorno de desarrollo.
if (($_ENV['APP_ENV'] ?? 'production') !== 'development') {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Not Found'
    ]);
    exit;
}

$controller = new DebugController();
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

// Estadísticas
if ($path === '/api/debug/stats' && $method === 'GET') {
    $controller->stats();
    exit;
}

// Logs
if ($path === '/api/debug/logs' && $method === 'GET') {
    $controller->logs();
    exit;
}

// Limpiar log
if ($path === '/api/debug/clear-log' && $method === 'POST') {
    $controller->clearLog();
    exit;
}

// Test DB
if ($path === '/api/debug/test-db' && $method === 'GET') {
    $controller->testDB();
    exit;
}

// PHP Info
if ($path === '/api/debug/phpinfo' && $method === 'GET') {
    $controller->phpinfo();
    exit;
}