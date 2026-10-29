<?php

declare(strict_types=1);

use App\Exceptions\GlobalExceptionHandler;
use App\Performance\PerformanceMetricsBootstrap;
use App\Http\Request;
use App\Http\Response;
use App\Middlewares\RateLimitMiddleware;
use App\Routes\Router;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

set_exception_handler(
    static function (\Throwable $exception): void {
        $response = GlobalExceptionHandler::handle($exception);
        $response->emit();
    }
);

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$request = Request::fromGlobals();
$method = $request->method();
$path = $request->path();

$metricsLifecycle = (new PerformanceMetricsBootstrap())->start($request);

require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../config/config.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

define('SRC_PATH', dirname(__DIR__) . '/src/');

define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

// ============================================
// CORS
// ============================================

$allowedOrigins = array_filter(
    array_map(
        'trim',
        explode(',', (string) ($_ENV['CORS_ALLOWED_ORIGINS'] ?? ''))
    )
);

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// ============================================
// SERVIDOR EMBEBIDO (php -S): servir archivos
// estáticos reales (uploads, imágenes, CSS/JS)
// sin pasarlos por el router.
// ============================================

if (PHP_SAPI === 'cli-server') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $archivoSolicitado = __DIR__ . parse_url($requestUri, PHP_URL_PATH);

    if (is_file($archivoSolicitado)) {
        return false;
    }
}

// ============================================
// CORS - PREFLIGHT
// ============================================

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Hacemos la variable $path global para que esté disponible en los routers
$GLOBALS['path'] = $path;

$router = new Router();

require_once SRC_PATH . 'Routes/api.php';

// Rutas de depuración: solo se montan (y por tanto responden)
// en entorno de desarrollo.
if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    $debugHandled = require_once SRC_PATH . 'Routes/debug_router.php';

    if ($debugHandled) {
        exit;
    }
}

if (
    $metricsLifecycle !== null
    && $method === 'GET'
    && $path === '/api/health'
) {
    $metricsLifecycle->setRoute('/api/health');
}

// Health
if ($method === 'GET' && $path === '/api/health') {
    Response::success([
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
    ], 200, 'API funcionando correctamente')->emit();
    exit;
}

RateLimitMiddleware::verificar();

try {
    $response = $router->dispatch($request);
} catch (\Throwable $exception) {
    if ($metricsLifecycle !== null) {
        $metricsLifecycle->setRoute($router->matchedRoute());
    }

    throw $exception;
}

if ($metricsLifecycle !== null) {
    $metricsLifecycle->setRoute($router->matchedRoute());
}

$response->emit();
exit;