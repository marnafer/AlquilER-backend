<?php

declare(strict_types=1);

const PERFORMANCE_LOG_BASE = 'storage/logs/performance.jsonl';
const PERFORMANCE_DASHBOARD_PATH = 'storage/performance-dashboard/index.html';
const PERFORMANCE_P95_TARGET_MS = 500.0;
const PERFORMANCE_ERROR_TARGET_PERCENT = 1.0;

$sessionFilter = null;
$openBrowser = true;

foreach (($argv ?? []) as $argument) {
    if ($argument === '--no-open') {
        $openBrowser = false;
        continue;
    }

    if (!str_starts_with($argument, '--session=')) {
        continue;
    }

    $sessionFilter = substr($argument, strlen('--session='));

    if (!preg_match('/^[a-f0-9]{32}$/', $sessionFilter)) {
        fwrite(STDERR, "Error: el identificador de sesión no es válido.\n");
        exit(1);
    }
}

/**
 * @return list<float>
 */
function performanceNumbers(array $values): array
{
    return array_map(
        static fn (mixed $value): float => (float) $value,
        $values
    );
}

/**
 * Percentil nearest-rank, igual que el script de carga del proyecto.
 *
 * @param list<float> $values
 */
function performancePercentile(array $values, int $percentile): float
{
    if ($values === []) {
        return 0.0;
    }

    sort($values);
    $index = (int) ceil(($percentile / 100) * count($values)) - 1;

    return $values[max(0, $index)] ?? 0.0;
}

function performanceEscape(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * @return array<string, mixed>
 */
function performanceReadEvent(string $line, string $file, int $lineNumber): array
{
    try {
        $event = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException(
            "JSON inválido en {$file}, línea {$lineNumber}.",
            0,
            $exception
        );
    }

    if (
        !is_array($event)
        || !isset(
            $event['timestamp_utc'],
            $event['method'],
            $event['status_code'],
            $event['duration_ms'],
            $event['sql']
        )
        || !is_string($event['timestamp_utc'])
        || !is_string($event['method'])
        || !is_numeric($event['status_code'])
        || !is_numeric($event['duration_ms'])
        || !is_array($event['sql'])
        || !isset(
            $event['sql']['query_count'],
            $event['sql']['total_ms'],
            $event['sql']['slow_query_count']
        )
        || !is_numeric($event['sql']['query_count'])
        || !is_numeric($event['sql']['total_ms'])
        || !is_numeric($event['sql']['slow_query_count'])
    ) {
        throw new RuntimeException(
            "Estructura de evento inválida en {$file}, línea {$lineNumber}."
        );
    }

    if (
        !is_finite((float) $event['duration_ms'])
        || (float) $event['duration_ms'] < 0
        || !is_finite((float) $event['sql']['total_ms'])
        || (float) $event['sql']['total_ms'] < 0
    ) {
        throw new RuntimeException(
            "Duración inválida en {$file}, línea {$lineNumber}."
        );
    }

    if (
        isset($event['route'])
        && $event['route'] !== null
        && !is_string($event['route'])
    ) {
        throw new RuntimeException(
            "Ruta inválida en {$file}, línea {$lineNumber}."
        );
    }

    return $event;
}

/**
 * @return list<array{method: string, route: string}>
 */
function performanceRouteInventory(string $projectRoot): array
{
    $routeFiles = [
        $projectRoot . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . 'Routes'
            . DIRECTORY_SEPARATOR . 'api.php',
    ];
    $routes = [];

    foreach ($routeFiles as $routeFile) {
        $source = file_get_contents($routeFile);

        if ($source === false) {
            throw new RuntimeException(
                "No se pudo leer el registro de rutas: {$routeFile}"
            );
        }

        preg_match_all(
            '~\$router->(get|post|put|patch|delete)\s*'
            . '\(\s*([\'"])(/api/[^\'"]+)\2~i',
            $source,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $routes[] = [
                'method' => strtoupper($match[1]),
                'route' => $match[3],
            ];
        }
    }

    $routes[] = [
        'method' => 'GET',
        'route' => '/api/health',
    ];

    $debugRouterPath = $projectRoot . DIRECTORY_SEPARATOR . 'src'
        . DIRECTORY_SEPARATOR . 'Routes'
        . DIRECTORY_SEPARATOR . 'debug_router.php';
    $debugSource = file_get_contents($debugRouterPath);

    if ($debugSource === false) {
        throw new RuntimeException(
            "No se pudo leer el registro de rutas: {$debugRouterPath}"
        );
    }

    preg_match_all(
        '~\$debugPath\s*===\s*([\'"])(/api/[^\'"]+)\1\s*'
        . '&&\s*\$debugMethod\s*===\s*([\'"])(GET|POST|PUT|PATCH|DELETE)\3~i',
        $debugSource,
        $debugMatches,
        PREG_SET_ORDER
    );

    foreach ($debugMatches as $match) {
        $routes[] = [
            'method' => strtoupper($match[4]),
            'route' => $match[2],
        ];
    }

    $uniqueRoutes = [];

    foreach ($routes as $route) {
        $key = $route['method'] . ' ' . $route['route'];
        $uniqueRoutes[$key] = $route;
    }

    usort(
        $uniqueRoutes,
        static fn (array $left, array $right): int =>
            [$left['route'], $left['method']]
            <=> [$right['route'], $right['method']]
    );

    return array_values($uniqueRoutes);
}

try {
    $projectRoot = dirname(__DIR__);
    $routeInventory = performanceRouteInventory($projectRoot);
    $logBasePath = $projectRoot
        . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, PERFORMANCE_LOG_BASE);

    $logFiles = [
        $logBasePath . '.3',
        $logBasePath . '.2',
        $logBasePath . '.1',
        $logBasePath,
    ];

    $events = [];

    foreach ($logFiles as $logFile) {
        if (!is_file($logFile)) {
            continue;
        }

        if (!is_readable($logFile)) {
            throw new RuntimeException(
                "No se puede leer el log: {$logFile}"
            );
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            throw new RuntimeException(
                "No se pudo cargar el log: {$logFile}"
            );
        }

        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $events[] = performanceReadEvent(
                $line,
                $logFile,
                $index + 1
            );
        }
    }

    $groups = [];
    $errorCount = 0;

    foreach ($routeInventory as $route) {
        $key = $route['method'] . ' ' . $route['route'];
        $groups[$key] = [
            'method' => $route['method'],
            'route' => $route['route'],
            'durations' => [],
            'sql_durations' => [],
            'requests' => 0,
            'errors' => 0,
            'queries' => 0,
            'slow_queries' => 0,
        ];
    }

    if ($sessionFilter !== null) {
        $events = array_values(
            array_filter(
                $events,
                static fn (array $event): bool =>
                    ($event['session_id'] ?? null) === $sessionFilter
            )
        );
    }

    foreach ($events as $event) {
        $route = $event['route'] ?? null;
        $routeLabel = is_string($route) ? $route : '(ruta no determinada)';
        $key = $event['method'] . ' ' . $routeLabel;
        $statusCode = (int) $event['status_code'];
        $isError = $statusCode >= 400;

        if ($isError) {
            $errorCount++;
        }

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'method' => $event['method'],
                'route' => $routeLabel,
                'durations' => [],
                'sql_durations' => [],
                'requests' => 0,
                'errors' => 0,
                'queries' => 0,
                'slow_queries' => 0,
            ];
        }

        $groups[$key]['requests']++;
        $groups[$key]['errors'] += $isError ? 1 : 0;
        $groups[$key]['durations'][] = (float) $event['duration_ms'];
        $groups[$key]['sql_durations'][] = (float) $event['sql']['total_ms'];
        $groups[$key]['queries'] += (int) $event['sql']['query_count'];
        $groups[$key]['slow_queries'] +=
            (int) $event['sql']['slow_query_count'];
    }

    $rows = [];

    foreach ($groups as $group) {
        $durations = performanceNumbers($group['durations']);
        $sqlDurations = performanceNumbers($group['sql_durations']);
        $requests = $group['requests'];
        $errorPercent = $requests > 0
            ? ($group['errors'] / $requests) * 100
            : 0.0;
        $p95 = $requests > 0
            ? performancePercentile($durations, 95)
            : null;

        $rows[] = [
            ...$group,
            'p50' => $requests > 0
                ? performancePercentile($durations, 50)
                : null,
            'p95' => $p95,
            'max' => $requests > 0 ? max($durations) : null,
            'sql_p95' => $requests > 0
                ? performancePercentile($sqlDurations, 95)
                : null,
            'error_percent' => $requests > 0 ? $errorPercent : null,
            'needs_review' => $requests > 0
                && (
                    $p95 > PERFORMANCE_P95_TARGET_MS
                    || $errorPercent > PERFORMANCE_ERROR_TARGET_PERCENT
                ),
        ];
    }

    usort(
        $rows,
        static function (array $left, array $right): int {
            if ($left['requests'] === 0 || $right['requests'] === 0) {
                return $right['requests'] <=> $left['requests'];
            }

            return $right['p95'] <=> $left['p95'];
        }
    );

    $totalRequests = count($events);
    $routeRows = '';

    foreach ($rows as $row) {
        $reviewClass = $row['needs_review'] ? ' class="review"' : '';
        $hasSamples = $row['requests'] > 0;
        $sampleLabel = $hasSamples ? (string) $row['requests'] : 'Sin datos';
        $p50 = $hasSamples ? number_format($row['p50'], 2) : '—';
        $p95 = $hasSamples ? number_format($row['p95'], 2) : '—';
        $max = $hasSamples ? number_format($row['max'], 2) : '—';
        $errorPercent = $hasSamples
            ? number_format($row['error_percent'], 2) . '%'
            : '—';
        $sqlP95 = $hasSamples ? number_format($row['sql_p95'], 2) : '—';
        $routeRows .= '<tr' . $reviewClass . '>'
            . '<td>' . performanceEscape($row['method']) . '</td>'
            . '<td>' . performanceEscape($row['route']) . '</td>'
            . '<td>' . $sampleLabel . '</td>'
            . '<td>' . $p50 . '</td>'
            . '<td>' . $p95 . '</td>'
            . '<td>' . $max . '</td>'
            . '<td>' . $errorPercent . '</td>'
            . '<td>' . $row['queries'] . '</td>'
            . '<td>' . $sqlP95 . '</td>'
            . '<td>' . $row['slow_queries'] . '</td>'
            . '</tr>';
    }

    $recentRows = '';

    foreach (array_slice(array_reverse($events), 0, 50) as $event) {
        $route = $event['route'] ?? null;
        $routeLabel = is_string($route)
            ? $route
            : '(ruta no determinada)';

        $recentRows .= '<tr>'
            . '<td>' . performanceEscape($event['timestamp_utc']) . '</td>'
            . '<td>' . performanceEscape($event['method']) . '</td>'
            . '<td>' . performanceEscape($routeLabel) . '</td>'
            . '<td>' . (int) $event['status_code'] . '</td>'
            . '<td>' . number_format((float) $event['duration_ms'], 2) . '</td>'
            . '<td>' . (int) $event['sql']['query_count'] . '</td>'
            . '<td>' . number_format((float) $event['sql']['total_ms'], 2) . '</td>'
            . '</tr>';
    }

    $generatedAt = gmdate('Y-m-d H:i:s') . ' UTC';

    $routeCount = count($rows);
    $html = <<<HTML
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Métricas locales de AlquilER</title>
<style>
:root { color-scheme: light; font-family: system-ui, sans-serif; }
body { margin: 0; padding: 2rem; background: #f3f5f8; color: #172033; }
main { max-width: 1500px; margin: auto; }
h1 { margin-bottom: .3rem; }
.note { color: #526078; }
.cards { display: flex; flex-wrap: wrap; gap: 1rem; margin: 1.5rem 0; }
.card, section { background: white; border-radius: .6rem; padding: 1rem; box-shadow: 0 2px 8px #17203312; }
.card strong { display: block; font-size: 1.5rem; }
section { margin: 1rem 0; overflow-x: auto; }
input { padding: .65rem; width: min(100%, 28rem); border: 1px solid #bac4d3; border-radius: .35rem; }
button, select { padding: .6rem .8rem; border: 1px solid #bac4d3; border-radius: .35rem; background: white; }
button { cursor: pointer; }
button:disabled { cursor: not-allowed; opacity: .55; }
.capture-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; }
.capture-actions button:first-child { color: white; border-color: #166534; background: #166534; }
.capture-actions button:nth-child(2) { color: white; border-color: #991b1b; background: #991b1b; }
table { border-collapse: collapse; width: 100%; margin-top: 1rem; white-space: nowrap; }
th, td { padding: .65rem; border-bottom: 1px solid #e3e7ed; text-align: left; }
th { background: #f7f8fa; }
tr.review { background: #fff1d6; }
small { color: #596579; }
</style>
</head>
<body>
<main>
<h1>Métricas locales de AlquilER</h1>
<p class="note" id="dashboard-summary">Generado: {$generatedAt}. Eventos analizados: {$totalRequests}; rutas registradas: {$routeCount}. Solo datos locales.</p>
<section class="capture-controls">
<h2>Captura por sesión</h2>
<p id="capture-status" class="note">Conecta el dashboard al servidor local para iniciar una captura.</p>
<div class="capture-actions">
<button id="capture-start" type="button">Iniciar captura</button>
<button id="capture-stop" type="button" disabled>Detener captura</button>
<label for="capture-session">Sesión:</label>
<select id="capture-session"><option value="">Historial general</option></select>
</div>
<small>La captura registra solo peticiones que la API atiende mientras está activa. La actualización es periódica, no instantánea.</small>
</section>
<div class="cards">
<div class="card">Solicitudes<strong id="request-count">{$totalRequests}</strong></div>
<div class="card">Rutas registradas<strong>{$routeCount}</strong></div>
<div class="card">Respuestas HTTP de error<strong id="error-count">{$errorCount}</strong></div>
</div>
<p class="note">“Sin datos” indica que todavía no se midieron solicitudes para esa ruta y método. Las filas resaltadas superan los objetivos orientativos: p95 &gt; 500 ms o errores &gt; 1 %. No son un SLA.</p>
<section>
<h2>Resumen por método y ruta</h2>
<input id="route-filter" type="search" placeholder="Filtrar por método o ruta">
<table id="route-table">
<thead><tr>
<th>Método</th><th>Plantilla de ruta</th><th>Solicitudes</th>
<th>HTTP p50 (ms)</th><th>HTTP p95 (ms)</th><th>HTTP máximo (ms)</th>
<th>Errores</th><th>Consultas SQL</th><th>SQL p95 (ms)</th><th>Consultas lentas</th>
</tr></thead>
<tbody id="route-summary">{$routeRows}</tbody>
</table>
</section>
<section>
<h2>Últimas 50 solicitudes</h2>
<table id="recent-table">
<thead><tr>
<th>Fecha UTC</th><th>Método</th><th>Ruta</th><th>Estado</th>
<th>HTTP (ms)</th><th>Consultas</th><th>SQL total (ms)</th>
</tr></thead>
<tbody id="recent-requests">{$recentRows}</tbody>
</table>
</section>
<small>HTTP incluye el tiempo total de la petición. SQL total es la suma informada por Illuminate; comparar ambas medidas en varias ejecuciones antes de concluir cuál es el cuello de botella.</small>
</main>
<script>
document.getElementById('route-filter').addEventListener('input', function () {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#route-table tbody tr').forEach(function (row) {
        row.hidden = !row.textContent.toLowerCase().includes(filter);
    });
});

(() => {
    const statusNode = document.getElementById('capture-status');
    const startButton = document.getElementById('capture-start');
    const stopButton = document.getElementById('capture-stop');
    const sessionSelect = document.getElementById('capture-session');
    const routeBody = document.getElementById('route-summary');
    const recentBody = document.getElementById('recent-requests');
    const requestCount = document.getElementById('request-count');
    const errorCount = document.getElementById('error-count');
    const summaryNode = document.getElementById('dashboard-summary');
    const routeInventory = Array.from(routeBody.rows).map(row => ({
        method: row.cells[0].textContent,
        route: row.cells[1].textContent
    }));
    const historicRouteRows = Array.from(routeBody.rows, row => row.cloneNode(true));
    const historicRecentRows = Array.from(recentBody.rows, row => row.cloneNode(true));
    const historicRequestCount = requestCount.textContent;
    const historicErrorCount = errorCount.textContent;
    const historicSummary = summaryNode.textContent;
    let csrfToken = '';
    let selectedSessionId = '';
    let activeSessionId = '';
    let metricsEnabled = false;
    let historySelected = false;

    const percentile = (values, percent) => {
        if (!values.length) return 0;
        const sorted = [...values].sort((a, b) => a - b);
        return sorted[Math.max(0, Math.ceil(percent / 100 * sorted.length) - 1)];
    };

    const addCell = (row, value) => {
        const cell = document.createElement('td');
        cell.textContent = String(value);
        row.appendChild(cell);
    };

    const renderEvents = (events) => {
        const groups = new Map();
        let errors = 0;

        events.forEach(event => {
            const route = event.route || '(ruta no determinada)';
            const key = `\${event.method} \${route}`;
            const group = groups.get(key) || {
                method: event.method,
                route,
                durations: [],
                sqlDurations: [],
                requests: 0,
                errors: 0,
                queries: 0,
                slowQueries: 0
            };
            const isError = Number(event.status_code) >= 400;
            group.requests += 1;
            group.errors += isError ? 1 : 0;
            group.durations.push(Number(event.duration_ms));
            group.sqlDurations.push(Number(event.sql.total_ms));
            group.queries += Number(event.sql.query_count);
            group.slowQueries += Number(event.sql.slow_query_count);
            groups.set(key, group);
            errors += isError ? 1 : 0;
        });

        const allRoutes = new Map();
        routeInventory.forEach(route => {
            allRoutes.set(`\${route.method} \${route.route}`, route);
        });
        groups.forEach((group, key) => allRoutes.set(key, group));

        const rows = Array.from(allRoutes.entries()).map(([key, route]) => {
            const group = groups.get(key);
            if (!group) return { ...route, requests: 0, p95: null };
            const errorPercent = group.errors / group.requests * 100;
            const p95 = percentile(group.durations, 95);
            return { ...group, errorPercent, p95 };
        });

        rows.sort((a, b) => {
            if (a.requests === 0 || b.requests === 0) {
                if (a.requests !== b.requests) return b.requests - a.requests;
                return `\${a.route} \${a.method}`.localeCompare(`\${b.route} \${b.method}`);
            }
            return b.p95 - a.p95;
        });

        routeBody.replaceChildren();
        rows.forEach(group => {
            const row = document.createElement('tr');
            if (group.requests > 0 && (group.p95 > 500 || group.errorPercent > 1)) {
                row.className = 'review';
            }
            addCell(row, group.method);
            addCell(row, group.route);
            addCell(row, group.requests || 'Sin datos');
            addCell(row, group.requests ? percentile(group.durations, 50).toFixed(2) : '—');
            addCell(row, group.requests ? group.p95.toFixed(2) : '—');
            addCell(row, group.requests ? Math.max(...group.durations).toFixed(2) : '—');
            addCell(row, group.requests ? `\${group.errorPercent.toFixed(2)}%` : '—');
            addCell(row, group.queries || 0);
            addCell(row, group.requests ? percentile(group.sqlDurations, 95).toFixed(2) : '—');
            addCell(row, group.slowQueries || 0);
            routeBody.appendChild(row);
        });

        const recent = [...events].reverse().slice(0, 50);
        recentBody.replaceChildren();
        recent.forEach(event => {
            const row = document.createElement('tr');
            addCell(row, event.timestamp_utc);
            addCell(row, event.method);
            addCell(row, event.route || '(ruta no determinada)');
            addCell(row, event.status_code);
            addCell(row, Number(event.duration_ms).toFixed(2));
            addCell(row, event.sql.query_count);
            addCell(row, Number(event.sql.total_ms).toFixed(2));
            recentBody.appendChild(row);
        });

        requestCount.textContent = String(events.length);
        errorCount.textContent = String(errors);
        const selected = sessionSelect.value;
        const sessionLabel = selected ? `Captura \${selected}` : 'Historial general';
        summaryNode.textContent = `\${sessionLabel}. Actualizado: \${new Date().toLocaleTimeString()}. Eventos analizados: \${events.length}; rutas registradas: \${rows.length}.`;
    };

    const restoreHistory = () => {
        routeBody.replaceChildren(...historicRouteRows.map(row => row.cloneNode(true)));
        recentBody.replaceChildren(...historicRecentRows.map(row => row.cloneNode(true)));
        requestCount.textContent = historicRequestCount;
        errorCount.textContent = historicErrorCount;
        summaryNode.textContent = historicSummary;
    };

    const refresh = async () => {
        if (location.protocol === 'file:') {
            statusNode.textContent = 'Para usar los controles, inicia el servidor local del dashboard y abre http://127.0.0.1:8765.';
            startButton.disabled = true;
            stopButton.disabled = true;
            sessionSelect.disabled = true;
            return;
        }

        try {
            const statusResponse = await fetch('?action=status', { cache: 'no-store' });
            const status = await statusResponse.json();
            if (!statusResponse.ok) throw new Error(status.error || 'No se pudo consultar el estado.');
            csrfToken = status.csrf_token;
            metricsEnabled = status.metrics_enabled;
            activeSessionId = status.active_session_id || '';

            const sessions = status.sessions || [];
            const requestedSession = selectedSessionId
                || (historySelected ? '' : activeSessionId || status.last_session_id)
                || '';
            sessionSelect.replaceChildren();
            sessions.slice().reverse().forEach(session => {
                const option = document.createElement('option');
                option.value = session.id;
                option.textContent = `\${session.started_at}\${session.stopped_at ? ` — \${session.stopped_at}` : ' — activa'}`;
                sessionSelect.appendChild(option);
            });
            const allOption = document.createElement('option');
            allOption.value = '';
            allOption.textContent = 'Historial general';
            sessionSelect.appendChild(allOption);
            selectedSessionId = sessions.some(session => session.id === requestedSession)
                ? requestedSession
                : '';
            sessionSelect.value = selectedSessionId;

            startButton.disabled = !metricsEnabled || Boolean(activeSessionId);
            stopButton.disabled = !activeSessionId;
            statusNode.textContent = activeSessionId
                ? `Captura activa desde \${sessions.find(item => item.id === activeSessionId)?.started_at || ''}. Actualizando cada 3 segundos.`
                : metricsEnabled
                    ? 'Sin captura activa. Inicia una para aislar las próximas peticiones.'
                    : 'Activa APP_ENV=development y PERFORMANCE_METRICS_ENABLED=true en el backend para capturar.';

            if (selectedSessionId) {
                const eventsResponse = await fetch(
                    `?action=events&session_id=\${encodeURIComponent(selectedSessionId)}`,
                    { cache: 'no-store' }
                );
                const payload = await eventsResponse.json();
                if (!eventsResponse.ok) throw new Error(payload.error || 'No se pudieron leer las métricas.');
                renderEvents(payload.events);
            } else {
                restoreHistory();
            }
        } catch (error) {
            statusNode.textContent = error.message || 'No se pudo conectar con el control local de métricas.';
        }
    };

    const sendControl = async action => {
        startButton.disabled = true;
        stopButton.disabled = true;
        try {
            const response = await fetch(`?action=\${action}`, {
                method: 'POST',
                headers: { 'X-Performance-Dashboard-Token': csrfToken }
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'No se pudo cambiar el estado de captura.');
            if (action === 'start') {
                selectedSessionId = result.session_id;
                historySelected = false;
                renderEvents([]);
            }
            await refresh();
        } catch (error) {
            statusNode.textContent = error.message;
            await refresh();
        }
    };

    startButton.addEventListener('click', () => sendControl('start'));
    stopButton.addEventListener('click', () => sendControl('stop'));
    sessionSelect.addEventListener('change', () => {
        selectedSessionId = sessionSelect.value;
        historySelected = !selectedSessionId;
        refresh();
    });

    refresh();
    window.setInterval(refresh, 3000);
})();
</script>
</body>
</html>
HTML;

    $outputPath = $projectRoot
        . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, PERFORMANCE_DASHBOARD_PATH);
    $outputDirectory = dirname($outputPath);

    if (
        !is_dir($outputDirectory)
        && !mkdir($outputDirectory, 0700, true)
        && !is_dir($outputDirectory)
    ) {
        throw new RuntimeException(
            "No se pudo crear el directorio {$outputDirectory}."
        );
    }

    if (file_put_contents($outputPath, $html, LOCK_EX) === false) {
        throw new RuntimeException(
            "No se pudo escribir el dashboard {$outputPath}."
        );
    }

    echo "Dashboard generado: {$outputPath}" . PHP_EOL;
    echo "Eventos analizados: {$totalRequests}" . PHP_EOL;

    if (PHP_OS_FAMILY === 'Windows' && $openBrowser) {
        $command = 'start "" ' . escapeshellarg($outputPath);
        $process = @popen($command, 'r');

        if (is_resource($process)) {
            pclose($process);
            echo 'Dashboard abierto en el navegador predeterminado.'
                . PHP_EOL;
        } else {
            echo 'No se pudo abrir el navegador automáticamente.'
                . PHP_EOL;
        }
    } elseif (PHP_OS_FAMILY !== 'Windows') {
        echo 'Abre el archivo HTML generado en tu navegador.'
            . PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'Error: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}