<?php

declare(strict_types=1);

use App\Performance\PerformanceMetricsReader;
use App\Performance\PerformanceMetricsSession;
use Dotenv\Dotenv;

$projectRoot = dirname(__DIR__);
require $projectRoot . DIRECTORY_SEPARATOR . 'vendor'
    . DIRECTORY_SEPARATOR . 'autoload.php';

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Este dashboard solo está disponible desde este equipo.');
}

Dotenv::createImmutable($projectRoot)->safeLoad();

$sessionManager = new PerformanceMetricsSession();
$action = $_GET['action'] ?? null;

function dashboardJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
    exit;
}

function dashboardMetricsEnabled(): bool
{
    return strtolower(trim((string) ($_ENV['APP_ENV'] ?? 'production')))
            === 'development'
        && strtolower(
            trim((string) ($_ENV['PERFORMANCE_METRICS_ENABLED'] ?? 'false'))
        ) === 'true';
}

try {
    if ($action === 'status') {
        $state = $sessionManager->status();

        dashboardJson([
            'metrics_enabled' => dashboardMetricsEnabled(),
            'active_session_id' => $state['active_session_id'],
            'last_session_id' => $state['last_session_id'],
            'sessions' => $state['sessions'],
            'csrf_token' => $state['csrf_token'],
        ]);
    }

    if ($action === 'events') {
        $environment = $_GET['environment'] ?? 'development';

        if (
            !is_string($environment)
            || !in_array($environment, ['development', 'production'], true)
        ) {
            dashboardJson(['error' => 'El entorno solicitado no es válido.'], 400);
        }

        $sessionId = $_GET['session_id'] ?? null;

        if ($sessionId !== null) {
            if (
                $environment !== 'development'
                || !is_string($sessionId)
                || !preg_match('/^[a-f0-9]{32}$/', $sessionId)
                || !in_array(
                    $sessionId,
                    array_column($sessionManager->sessions(), 'id'),
                    true
                )
            ) {
                dashboardJson(['error' => 'La sesión solicitada no existe.'], 404);
            }
        }

        $events = (new PerformanceMetricsReader())->read(
            $environment,
            $sessionId
        );

        dashboardJson([
            'events' => $events,
        ]);
    }

    if ($action === 'start' || $action === 'stop') {
        if (($_GET['environment'] ?? 'development') !== 'development') {
            dashboardJson([
                'error' => 'Las capturas solo están disponibles en desarrollo.',
            ], 409);
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            dashboardJson(['error' => 'Método no permitido.'], 405);
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $requestHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $csrfToken = $_SERVER['HTTP_X_PERFORMANCE_DASHBOARD_TOKEN'] ?? '';
        $state = $sessionManager->status();

        if (
            !is_string($origin)
            || !hash_equals('http://' . $requestHost, strtolower($origin))
            || !is_string($csrfToken)
            || !hash_equals($state['csrf_token'], $csrfToken)
        ) {
            dashboardJson(['error' => 'Solicitud local no autorizada.'], 403);
        }

        if ($action === 'start') {
            if (!dashboardMetricsEnabled()) {
                dashboardJson([
                    'error' => 'Activa APP_ENV=development y PERFORMANCE_METRICS_ENABLED=true en el backend.',
                ], 409);
            }

            try {
                $sessionId = $sessionManager->start();
            } catch (RuntimeException $exception) {
                dashboardJson(['error' => $exception->getMessage()], 409);
            }

            dashboardJson(['session_id' => $sessionId]);
        }

        $sessionId = $sessionManager->stop();

        if ($sessionId === null) {
            dashboardJson(['error' => 'No hay una captura activa.'], 409);
        }

        dashboardJson(['session_id' => $sessionId]);
    }

    if ($action !== null) {
        dashboardJson(['error' => 'Acción desconocida.'], 404);
    }

    $dashboardPath = $projectRoot
        . DIRECTORY_SEPARATOR
        . 'storage'
        . DIRECTORY_SEPARATOR
        . 'performance-dashboard'
        . DIRECTORY_SEPARATOR
        . 'index.html';

    if (!is_file($dashboardPath) || !is_readable($dashboardPath)) {
        http_response_code(404);
        exit(
            'No existe el dashboard. Ejecuta php scripts/generar_dashboard_performance.php '
            . 'desde la raíz del proyecto y vuelve a cargar esta página.'
        );
    }

    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, private');
    readfile($dashboardPath);
} catch (Throwable $exception) {
    error_log(
        'Error en el dashboard local de rendimiento ('
        . get_class($exception)
        . '): '
        . $exception->getMessage()
    );

    dashboardJson([
        'error' => 'No se pudo procesar la solicitud del dashboard local.',
    ], 500);
}
