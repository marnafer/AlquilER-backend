<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

require_once __DIR__ . '/../config/config.php';

use App\Helpers\Response;
use App\Routes\Router;

date_default_timezone_set('America/Argentina/Buenos_Aires');
error_reporting(E_ALL);

define('SRC_PATH', dirname(__DIR__) . '/src/');

define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')); // Esto es útil para generar URLs relativas a la raíz del proyecto, 
                                                                  // especialmente si no está en la raíz del servidor web.  
ini_set('display_errors', 1);

// ============================================
// CONFIGURACIÓN
// ============================================

$method = strtoupper(trim($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

$path_bruto = parse_url($requestUri, PHP_URL_PATH);

$scriptName = $_SERVER['SCRIPT_NAME'];
$baseDir = str_replace('\\', '/', dirname(dirname($scriptName)));

if ($baseDir !== '/' && strpos($path_bruto, $baseDir) === 0) {
    $path_bruto = substr($path_bruto, strlen($baseDir));
}

if (strpos($path_bruto, '/public') === 0) {
    $path_bruto = substr($path_bruto, strlen('/public'));
}

$path = '/' . trim((string)$path_bruto, "/");

// Hacemos la variable $path global para que esté disponible en los routers
$GLOBALS['path'] = $path;

$router = new Router();

require_once SRC_PATH . 'routes/api.php';

$router->dispatch($method, $path);
exit;

// ============================================
// DEBUG
// ============================================

//require_once dirname(__DIR__) . '/src/debug/Debugger.php';

//use App\Debug\Debugger;

//if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
//    Debugger::setEnabled(true);
//    Debugger::enableErrorReporting();
//}

// Debugger::request();

// ============================================
// RUTAS DEL SISTEMA (respuestas rápidas)
// ============================================

// Health
if ($path === '/health') {
    Response::success([
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'php' => phpversion()
    ], 200, 'API funcionando correctamente');
    exit;
}

if ($path === '/') {
    // Endpoints sin escapes
    $endpoints = [
        '/health',
        '/api/categorias',
        '/api/provincias',
        '/api/localidades',
        '/api/usuarios',
        '/api/propiedades',
        '/api/servicios',
        '/api/propiedades-servicios',
        '/api/reservas',
        '/api/resenas',
        '/api/consultas',
        '/api/favoritos',
        '/api/logs-actividad',
        '/api/roles',
        '/api/propiedad-imagenes',
        '/api/debug/stats',
        '/api/debug/test-db'
    ];
    
    Response::success([
        'message' => 'API Alquiler Permanente funcionando',
        'endpoints' => $endpoints
    ], 200, 'Bienvenido a la API');
    exit;
}
