<?php

declare(strict_types=1);

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

function dashboardReadSessionEvents(string $sessionId): array
{
    $projectRoot = dirname(__DIR__);
    $logBasePath = $projectRoot
        . DIRECTORY_SEPARATOR
        . 'storage'
        . DIRECTORY_SEPARATOR
        . 'logs'
        . DIRECTORY_SEPARATOR
        . 'performance.jsonl';

    if (!is_dir(dirname($logBasePath))) {
        return [];
    }

    $lock = @fopen($logBasePath . '.lock', 'c');

    if ($lock === false) {
        throw new RuntimeException('No se pudo abrir el bloqueo del log de métricas.');
    }

    try {
        if (!@flock($lock, LOCK_SH)) {
            throw new RuntimeException('No se pudo bloquear el log de métricas para lectura.');
        }

        $events = [];

        foreach ([
            $logBasePath . '.3',
            $logBasePath . '.2',
            $logBasePath . '.1',
            $logBasePath,
        ] as $logPath) {
            if (!is_file($logPath)) {
                continue;
            }

            if (!is_readable($logPath)) {
                throw new RuntimeException("No se puede leer el log: {$logPath}");
            }

            $lines = file($logPath, FILE_IGNORE_NEW_LINES);

            if ($lines === false) {
                throw new RuntimeException("No se pudo cargar el log: {$logPath}");
            }

            foreach ($lines as $lineNumber => $line) {
                if (trim($line) === '') {
                    continue;
                }

                $event = json_decode($line, true);

                if (!is_array($event)) {
                    throw new RuntimeException(
                        "JSON inválido en {$logPath}, línea " . ($lineNumber + 1) . '.'
                    );
                }

                if (($event['session_id'] ?? null) !== $sessionId) {
                    continue;
                }

                $events[] = [
                    'timestamp_utc' => $event['timestamp_utc'] ?? '',
                    'method' => $event['method'] ?? '',
                    'route' => $event['route'] ?? null,
                    'status_code' => (int) ($event['status_code'] ?? 0),
                    'duration_ms' => (float) ($event['duration_ms'] ?? 0),
                    'sql' => [
                        'query_count' => (int) ($event['sql']['query_count'] ?? 0),
                        'total_ms' => (float) ($event['sql']['total_ms'] ?? 0),
                        'slow_query_count' => (int) (
                            $event['sql']['slow_query_count'] ?? 0
                        ),
                    ],
                ];
            }
        }

        return $events;
    } finally {
        @flock($lock, LOCK_UN);
        fclose($lock);
    }
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
        $sessionId = $_GET['session_id'] ?? '';

        if (
            !is_string($sessionId)
            || !preg_match('/^[a-f0-9]{32}$/', $sessionId)
            || !in_array(
                $sessionId,
                array_column($sessionManager->sessions(), 'id'),
                true
            )
        ) {
            dashboardJson(['error' => 'La sesión solicitada no existe.'], 404);
        }

        dashboardJson([
            'events' => dashboardReadSessionEvents($sessionId),
        ]);
    }

    if ($action === 'start' || $action === 'stop') {
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
