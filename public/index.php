<?php declare(strict_types=1);

    require_once __DIR__ . '/../vendor/autoload.php';

    use App\Exceptions\GlobalExceptionHandler;
    use Dotenv\Dotenv;

    set_exception_handler(
        [GlobalExceptionHandler::class, 'handle']
    );

    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();

    require_once __DIR__ . '/../src/database.php';

    require_once __DIR__ . '/../config/config.php';

    use App\Helpers\Response;
    use App\Routes\Router;
    use App\Middlewares\RateLimitMiddleware;
    
    date_default_timezone_set('America/Argentina/Buenos_Aires');

    define('SRC_PATH', dirname(__DIR__) . '/src/');

    define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')); // Esto es útil para generar URLs relativas a la raíz del proyecto, 
                                                                    // especialmente si no está en la raíz del servidor web.  

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
    // CONFIGURACIÓN
    // ============================================

    $method = strtoupper(trim($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

    // ============================================
    // SERVIDOR EMBEBIDO (php -S): servir archivos
    // estáticos reales (uploads, imágenes, CSS/JS)
    // sin pasarlos por el router.
    // ============================================

    if (PHP_SAPI === 'cli-server') {
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

    // Health
    if ($method === 'GET' && $path === '/api/health') {
        Response::success([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s')
        ], 200, 'API funcionando correctamente');
        exit;
    }

    $router = new Router();

    require_once SRC_PATH . 'routes/api.php';

    // Rutas de depuración: solo se montan (y por tanto responden)
    // en entorno de desarrollo.
    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
        require_once SRC_PATH . 'routes/debug_router.php';
    }

    RateLimitMiddleware::verificar();

    $router->dispatch($method, $path);
    exit;