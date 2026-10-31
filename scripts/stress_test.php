<?php

declare(strict_types=1);

const DEFAULT_BASE_URL = 'http://127.0.0.1:8000';
const DEFAULT_PATHS = ['/api/propiedades?per_page=10'];
const DEFAULT_CONCURRENCY_LEVELS = [1, 5, 10];
const DEFAULT_REQUESTS_PER_LEVEL = 30;
const DEFAULT_WARMUP_PER_PATH = 2;
const DEFAULT_P95_TARGET_MS = 500.0;
const DEFAULT_ERROR_TARGET_PERCENT = 1.0;
const MAX_TOTAL_REQUESTS = 10000;
const MAX_REQUESTS_PER_LEVEL = 10000;
const MAX_CONCURRENCY = 50;
const MAX_CONCURRENCY_LEVELS = 10;
const MAX_WARMUP_PER_PATH = 100;
const MAX_TIMEOUT_SECONDS = 120;

function printUsage(): void
{
    echo <<<'HELP'
Prueba de carga escalonada de AlquilER (solo solicitudes GET locales).

Uso:
  php scripts/stress_test.php [opciones]

Opciones:
  --base-url=URL          URL local (default: http://127.0.0.1:8000)
  --paths=RUTAS            Rutas GET bajo /api/, separadas por coma
  --requests=N             Solicitudes medidas por nivel (default: 30)
  --concurrency=N,...      Niveles de concurrencia (default: 1,5,10)
  --warmup=N               Calentamiento por ruta y nivel (default: 2)
  --timeout=N              Timeout por solicitud en segundos (default: 15)
  --p95-target-ms=N        Objetivo orientativo de latencia p95 (default: 500)
  --error-target-percent=N Porcentaje máximo de errores HTTP/cliente (default: 1)
  --help                   Muestra esta ayuda

Ejemplo:
  php scripts/stress_test.php --requests=30 --concurrency=1,5,10

Las rutas predeterminadas prueban /api/propiedades?per_page=10.
Solo se permiten destinos localhost/127.0.0.1/::1. No se envían
operaciones de escritura. Para endpoints autenticados se puede definir
STRESS_TEST_BEARER_TOKEN en el entorno; el token no se guarda en el reporte.

Los resultados JSON y CSV se guardan en storage/stress-tests/.
HELP;
    echo PHP_EOL;
}

/**
 * @return array<string, string>
 */
function parseOptions(array $arguments): array
{
    $options = [
        'base-url' => DEFAULT_BASE_URL,
        'paths' => implode(',', DEFAULT_PATHS),
        'requests' => (string) DEFAULT_REQUESTS_PER_LEVEL,
        'concurrency' => implode(',', DEFAULT_CONCURRENCY_LEVELS),
        'warmup' => (string) DEFAULT_WARMUP_PER_PATH,
        'timeout' => '15',
        'p95-target-ms' => (string) DEFAULT_P95_TARGET_MS,
        'error-target-percent' => (string) DEFAULT_ERROR_TARGET_PERCENT,
    ];

    foreach ($arguments as $argument) {
        if ($argument === '--help') {
            printUsage();
            exit(0);
        }

        if (!str_starts_with($argument, '--') || !str_contains($argument, '=')) {
            throw new InvalidArgumentException(
                "Opción inválida: {$argument}. Use --help para ver el uso."
            );
        }

        [$name, $value] = explode('=', substr($argument, 2), 2);

        if (!array_key_exists($name, $options)) {
            throw new InvalidArgumentException(
                "Opción desconocida: --{$name}. Use --help para ver el uso."
            );
        }

        $options[$name] = $value;
    }

    return $options;
}

function positiveInteger(string $value, string $option): int
{
    $parsed = filter_var($value, FILTER_VALIDATE_INT);

    if ($parsed === false || $parsed < 1) {
        throw new InvalidArgumentException(
            "--{$option} debe ser un entero positivo."
        );
    }

    return $parsed;
}

function nonNegativeInteger(string $value, string $option): int
{
    $parsed = filter_var($value, FILTER_VALIDATE_INT);

    if ($parsed === false || $parsed < 0) {
        throw new InvalidArgumentException(
            "--{$option} debe ser un entero igual o mayor que cero."
        );
    }

    return $parsed;
}

function nonNegativeFloat(string $value, string $option): float
{
    if (!is_numeric($value) || (float) $value < 0) {
        throw new InvalidArgumentException(
            "--{$option} debe ser un número igual o mayor que cero."
        );
    }

    return (float) $value;
}

function validateBaseUrl(string $baseUrl): string
{
    $parts = parse_url($baseUrl);
    $allowedHosts = ['localhost', '127.0.0.1', '::1'];

    if (
        $parts === false
        || !isset($parts['scheme'], $parts['host'])
        || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
        || !in_array(strtolower(trim($parts['host'], '[]')), $allowedHosts, true)
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['query'])
        || isset($parts['fragment'])
    ) {
        throw new InvalidArgumentException(
            '--base-url debe ser una URL HTTP(S) local sin credenciales, query ni fragmento.'
        );
    }

    return rtrim($baseUrl, '/');
}

/**
 * @return list<string>
 */
function parsePaths(string $paths): array
{
    $parsedPaths = array_values(array_filter(
        array_map('trim', explode(',', $paths)),
        static fn (string $path): bool => $path !== ''
    ));

    if ($parsedPaths === []) {
        throw new InvalidArgumentException(
            '--paths debe incluir al menos una ruta.'
        );
    }

    foreach ($parsedPaths as $path) {
        $pathParts = parse_url($path);

        if (
            $pathParts === false
            || !isset($pathParts['path'])
            || !str_starts_with($pathParts['path'], '/api/')
            || isset($pathParts['scheme'])
            || isset($pathParts['host'])
            || str_contains($path, '#')
        ) {
            throw new InvalidArgumentException(
                "Ruta no permitida: {$path}. Use rutas relativas bajo /api/."
            );
        }
    }

    return $parsedPaths;
}

/**
 * @return list<int>
 */
function parseConcurrencyLevels(string $value): array
{
    $levels = array_values(array_unique(array_map(
        static fn (string $item): int => positiveInteger(
            trim($item),
            'concurrency'
        ),
        explode(',', $value)
    )));

    if (count($levels) > MAX_CONCURRENCY_LEVELS) {
        throw new InvalidArgumentException(
            'No se permiten más de ' . MAX_CONCURRENCY_LEVELS
            . ' niveles de concurrencia.'
        );
    }

    foreach ($levels as $level) {
        if ($level > MAX_CONCURRENCY) {
            throw new InvalidArgumentException(
                '--concurrency no puede superar ' . MAX_CONCURRENCY . '.'
            );
        }
    }

    return $levels;
}

/**
 * @return array<string, mixed>
 */
function projectSnapshot(string $projectRoot): array
{
    $runGit = static function (string $arguments) use ($projectRoot): ?string {
        $command = 'git -C '
            . escapeshellarg($projectRoot)
            . ' '
            . $arguments
            . ' 2>&1';

        $output = shell_exec($command);

        return is_string($output) ? trim($output) : null;
    };

    $gitStatus = $runGit('status --porcelain') ?? '';
    $composerPath = $projectRoot . DIRECTORY_SEPARATOR . 'composer.json';
    $composer = json_decode(
        (string) file_get_contents($composerPath),
        true
    );

    return [
        'captured_at' => date(DATE_ATOM),
        'project' => basename($projectRoot),
        'git_branch' => $runGit('branch --show-current'),
        'git_commit' => $runGit('rev-parse HEAD'),
        'working_tree_dirty' => $gitStatus !== '',
        'git_changed_files' => $gitStatus === ''
            ? []
            : array_map(
                static fn (string $line): string => trim(substr($line, 3)),
                explode("\n", $gitStatus)
            ),
        'client_environment' => [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'operating_system' => PHP_OS_FAMILY,
            'cpu_count' => getenv('NUMBER_OF_PROCESSORS') !== false
                ? (int) getenv('NUMBER_OF_PROCESSORS')
                : null,
            'php_memory_limit' => ini_get('memory_limit'),
        ],
        'composer_require' => is_array($composer)
            ? ($composer['require'] ?? [])
            : [],
    ];
}

/**
 * @param list<string> $requestPaths
 * @return array{samples: list<array<string, mixed>>, elapsed_seconds: float}
 */
function sendRequests(
    string $baseUrl,
    array $requestPaths,
    int $concurrency,
    int $timeout
): array {
    $multiHandle = curl_multi_init();
    $activeHandles = [];
    $handleHeaders = [];
    $samples = [];
    $nextRequest = 0;
    $startedAt = microtime(true);
    $bearerToken = getenv('STRESS_TEST_BEARER_TOKEN');

    try {
        do {
            while (
                $nextRequest < count($requestPaths)
                && count($activeHandles) < $concurrency
            ) {
                $path = $requestPaths[$nextRequest];
                $handle = curl_init($baseUrl . $path);

                if ($handle === false) {
                    throw new RuntimeException(
                        'No se pudo crear una solicitud cURL.'
                    );
                }

                $headers = ['Accept: application/json'];

                if ($bearerToken !== false && $bearerToken !== '') {
                    $headers[] = 'Authorization: Bearer ' . $bearerToken;
                }

                $handleId = spl_object_id($handle);
                $handleHeaders[$handleId] = [];

                curl_setopt_array($handle, [
                    CURLOPT_WRITEFUNCTION => static fn ($curl, string $data): int =>
                        strlen($data),
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => min($timeout, 10),
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_HTTPGET => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_USERAGENT => 'AlquilER-Local-Stress-Test/2.0',
                    CURLOPT_HEADERFUNCTION => static function (
                        $curl,
                        string $headerLine
                    ) use (&$handleHeaders, $handleId): int {
                        $separator = strpos($headerLine, ':');

                        if ($separator !== false) {
                            $name = strtolower(trim(substr($headerLine, 0, $separator)));
                            $handleHeaders[$handleId][$name] = trim(
                                substr($headerLine, $separator + 1)
                            );
                        }

                        return strlen($headerLine);
                    },
                ]);

                $activeHandles[$handleId] = [
                    'handle' => $handle,
                    'path' => $path,
                ];

                $result = curl_multi_add_handle($multiHandle, $handle);

                if ($result !== CURLM_OK) {
                    throw new RuntimeException(
                        'No se pudo agregar una solicitud cURL: '
                        . curl_multi_strerror($result)
                    );
                }

                $nextRequest++;
            }

            do {
                $multiResult = curl_multi_exec($multiHandle, $running);
            } while ($multiResult === CURLM_CALL_MULTI_PERFORM);

            if ($multiResult !== CURLM_OK) {
                throw new RuntimeException(
                    'Falló el ejecutor cURL: '
                    . curl_multi_strerror($multiResult)
                );
            }

            while (($info = curl_multi_info_read($multiHandle)) !== false) {
                $handle = $info['handle'];
                $handleId = spl_object_id($handle);
                $request = $activeHandles[$handleId];
                $responseHeaders = $handleHeaders[$handleId] ?? [];
                $curlError = $info['result'] === CURLE_OK
                    ? null
                    : curl_error($handle);

                $samples[] = [
                    'path' => $request['path'],
                    'status_code' => (int) curl_getinfo(
                        $handle,
                        CURLINFO_RESPONSE_CODE
                    ),
                    'curl_error' => $curlError,
                    'timing_ms' => [
                        'dns' => round((float) curl_getinfo(
                            $handle,
                            CURLINFO_NAMELOOKUP_TIME
                        ) * 1000, 2),
                        'connect' => round((float) curl_getinfo(
                            $handle,
                            CURLINFO_CONNECT_TIME
                        ) * 1000, 2),
                        'time_to_first_byte' => round((float) curl_getinfo(
                            $handle,
                            CURLINFO_STARTTRANSFER_TIME
                        ) * 1000, 2),
                        'total' => round((float) curl_getinfo(
                            $handle,
                            CURLINFO_TOTAL_TIME
                        ) * 1000, 2),
                    ],
                    'downloaded_bytes' => (int) curl_getinfo(
                        $handle,
                        CURLINFO_SIZE_DOWNLOAD_T
                    ),
                    'rate_limit' => [
                        'limit' => $responseHeaders['x-ratelimit-limit'] ?? null,
                        'remaining' => $responseHeaders['x-ratelimit-remaining'] ?? null,
                        'retry_after' => $responseHeaders['retry-after'] ?? null,
                    ],
                ];

                curl_multi_remove_handle($multiHandle, $handle);
                curl_close($handle);
                unset($activeHandles[$handleId]);
                unset($handleHeaders[$handleId]);
            }

            if ($running > 0) {
                $selected = curl_multi_select($multiHandle, 1.0);

                if ($selected === -1) {
                    usleep(1000);
                }
            }
        } while ($nextRequest < count($requestPaths) || $activeHandles !== []);
    } finally {
        foreach ($activeHandles as $activeHandle) {
            curl_multi_remove_handle(
                $multiHandle,
                $activeHandle['handle']
            );
            curl_close($activeHandle['handle']);
        }

        curl_multi_close($multiHandle);
    }

    return [
        'samples' => $samples,
        'elapsed_seconds' => microtime(true) - $startedAt,
    ];
}

/**
 * @param list<array<string, mixed>> $samples
 * @return array<string, mixed>
 */
function summarizeSamples(
    array $samples,
    float $elapsedSeconds,
    float $p95TargetMs,
    float $errorTargetPercent
): array {
    $latencies = [];
    $timingComponents = [
        'dns' => [],
        'connect' => [],
        'time_to_first_byte' => [],
    ];
    $statusCodes = [];
    $errors = [];
    $rateLimitHits = 0;
    $downloadedBytes = 0;
    $rateLimitHeaders = [];

    foreach ($samples as $sample) {
        $status = (int) $sample['status_code'];
        $statusCodes[(string) $status] =
            ($statusCodes[(string) $status] ?? 0) + 1;
        $latencies[] = (float) $sample['timing_ms']['total'];

        foreach (array_keys($timingComponents) as $component) {
            $timingComponents[$component][] =
                (float) $sample['timing_ms'][$component];
        }

        $downloadedBytes += (int) $sample['downloaded_bytes'];

        if ($status === 429) {
            $rateLimitHits++;
        } elseif (
            $sample['curl_error'] !== null
            || $status < 200
            || $status >= 300
        ) {
            $errors[] = [
                'path' => $sample['path'],
                'status_code' => $status,
                'message' => $sample['curl_error']
                    ?? "Respuesta HTTP {$status}",
            ];
        }

        if ($sample['rate_limit']['limit'] !== null) {
            $rateLimitHeaders['limit'] = $sample['rate_limit']['limit'];
        }

        if ($sample['rate_limit']['remaining'] !== null) {
            $rateLimitHeaders['remaining'] = $sample['rate_limit']['remaining'];
        }

        if ($sample['rate_limit']['retry_after'] !== null) {
            $rateLimitHeaders['retry_after'] =
                $sample['rate_limit']['retry_after'];
        }
    }

    sort($latencies);
    ksort($statusCodes, SORT_NUMERIC);
    $requestCount = count($samples);
    $successfulCount = 0;

    foreach ($statusCodes as $code => $count) {
        if ((int) $code >= 200 && (int) $code < 300) {
            $successfulCount += $count;
        }
    }

    $errorPercent = $requestCount > 0
        ? round(count($errors) / $requestCount * 100, 2)
        : 0;
    $p95 = percentile($latencies, 95);
    $status = assessPerformance(
        $requestCount,
        $rateLimitHits,
        $errorPercent,
        $p95,
        $p95TargetMs,
        $errorTargetPercent
    );

    return [
        'requests' => $requestCount,
        'successful_responses' => $successfulCount,
        'failed_responses' => count($errors),
        'rate_limited_responses' => $rateLimitHits,
        'error_percent' => $errorPercent,
        'requests_per_second' => $elapsedSeconds > 0
            ? round($requestCount / $elapsedSeconds, 2)
            : 0,
        'successful_requests_per_second' => $elapsedSeconds > 0
            ? round($successfulCount / $elapsedSeconds, 2)
            : 0,
        'duration_seconds' => round($elapsedSeconds, 3),
        'latency_ms' => summarizeDistribution($latencies),
        'latency_components_ms' => array_map(
            'summarizeDistribution',
            $timingComponents
        ),
        'http_status_codes' => $statusCodes,
        'downloaded_bytes' => $downloadedBytes,
        'rate_limit_headers_observed' => $rateLimitHeaders,
        'errors' => array_slice($errors, 0, 50),
        'errors_truncated' => max(0, count($errors) - 50),
        'assessment' => $status,
    ];
}

/**
 * @param list<float> $values
 * @return array<string, float>
 */
function summarizeDistribution(array $values): array
{
    sort($values);

    return [
        'min' => round($values[0] ?? 0, 2),
        'average' => round(
            array_sum($values) / max(count($values), 1),
            2
        ),
        'p50' => percentile($values, 50),
        'p95' => percentile($values, 95),
        'p99' => percentile($values, 99),
        'max' => round(
            $values[count($values) - 1] ?? 0,
            2
        ),
    ];
}

function assessPerformance(
    int $requestCount,
    int $rateLimitHits,
    float $errorPercent,
    float $p95,
    float $p95TargetMs,
    float $errorTargetPercent
): string {
    if ($requestCount === 0) {
        return 'SIN DATOS';
    }

    if ($rateLimitHits > 0) {
        return 'INCONCLUSO: el limitador respondió 429';
    }

    if (
        $errorPercent > $errorTargetPercent
        || $p95 > $p95TargetMs
    ) {
        return 'REVISAR: supera uno o más objetivos';
    }

    if ($requestCount < 30) {
        return 'PROVISIONAL: cumple objetivos con muestra pequeña';
    }

    return 'OK para esta carga y estos objetivos';
}

function overallAssessment(array $scenarios): string
{
    $hasProvisionalResult = false;
    $hasInconclusiveResult = false;

    foreach ($scenarios as $scenario) {
        if ($scenario['warmup']['rate_limited_responses'] > 0) {
            $hasInconclusiveResult = true;
        }

        foreach ($scenario['by_path'] as $metrics) {
            $assessment = $metrics['assessment'];

            if (str_starts_with($assessment, 'REVISAR:')) {
                return 'REVISAR: una o más rutas superaron los objetivos';
            }

            if (str_starts_with($assessment, 'INCONCLUSO:')) {
                $hasInconclusiveResult = true;
            } elseif (str_starts_with($assessment, 'PROVISIONAL:')) {
                $hasProvisionalResult = true;
            }
        }
    }

    if ($hasInconclusiveResult) {
        return 'INCONCLUSO: el limitador afectó la medición';
    }

    if ($hasProvisionalResult) {
        return 'PROVISIONAL: muestra pequeña';
    }

    return 'OK para los escenarios y objetivos probados';
}

/**
 * @param list<float> $values
 */
function percentile(array $values, int $percentile): float
{
    if ($values === []) {
        return 0;
    }

    $index = (int) ceil(($percentile / 100) * count($values)) - 1;

    return round($values[max(0, $index)] ?? 0, 2);
}

/**
 * @param list<array<string, mixed>> $samples
 * @return array<string, array<string, mixed>>
 */
function summarizeByPath(
    array $samples,
    float $elapsedSeconds,
    float $p95TargetMs,
    float $errorTargetPercent
): array {
    $groups = [];

    foreach ($samples as $sample) {
        $groups[$sample['path']][] = $sample;
    }

    $summaries = [];

    foreach ($groups as $path => $pathSamples) {
        $summaries[$path] = summarizeSamples(
            $pathSamples,
            $elapsedSeconds,
            $p95TargetMs,
            $errorTargetPercent
        );
    }

    return $summaries;
}

/**
 * @param array<string, mixed> $report
 */
function writeCsvReport(array $report, string $path): void
{
    $handle = fopen($path, 'wb');

    if ($handle === false) {
        throw new RuntimeException("No se pudo crear el CSV: {$path}");
    }

    try {
        fputcsv($handle, [
            'concurrency',
            'path',
            'requests',
            'successful',
            'errors',
            'rate_limited_429',
            'error_percent',
            'rps',
            'latency_min_ms',
            'latency_avg_ms',
            'latency_p50_ms',
            'latency_p95_ms',
            'latency_p99_ms',
            'latency_max_ms',
            'connect_p95_ms',
            'ttfb_p95_ms',
            'assessment',
        ]);

        foreach ($report['scenarios'] as $scenario) {
            foreach ($scenario['by_path'] as $path => $metrics) {
                fputcsv($handle, [
                    $scenario['concurrency'],
                    $path,
                    $metrics['requests'],
                    $metrics['successful_responses'],
                    $metrics['failed_responses'],
                    $metrics['rate_limited_responses'],
                    $metrics['error_percent'],
                    $metrics['successful_requests_per_second'],
                    $metrics['latency_ms']['min'],
                    $metrics['latency_ms']['average'],
                    $metrics['latency_ms']['p50'],
                    $metrics['latency_ms']['p95'],
                    $metrics['latency_ms']['p99'],
                    $metrics['latency_ms']['max'],
                    $metrics['latency_components_ms']['connect']['p95'],
                    $metrics['latency_components_ms']['time_to_first_byte']['p95'],
                    $metrics['assessment'],
                ]);
            }
        }
    } finally {
        fclose($handle);
    }
}

function printScenarioSummary(array $scenarios): void
{
    echo PHP_EOL . 'Resumen por nivel de concurrencia y ruta' . PHP_EOL;
    echo str_repeat('-', 125) . PHP_EOL;
    printf(
        "%-12s %-40s %7s %9s %9s %9s %10s %s" . PHP_EOL,
        'Concurrencia',
        'Ruta',
        'Req.',
        'p95 (ms)',
        'TTFB p95',
        'Errores %',
        'Req./s',
        'Evaluación'
    );
    echo str_repeat('-', 125) . PHP_EOL;

    foreach ($scenarios as $scenario) {
        foreach ($scenario['by_path'] as $path => $metrics) {
            printf(
                "%-12d %-40s %7d %9.2f %9.2f %9.2f %10.2f %s" . PHP_EOL,
                $scenario['concurrency'],
                substr($path, 0, 40),
                $metrics['requests'],
                $metrics['latency_ms']['p95'],
                $metrics['latency_components_ms']['time_to_first_byte']['p95'],
                $metrics['error_percent'],
                $metrics['successful_requests_per_second'],
                $metrics['assessment']
            );
        }
    }

    echo str_repeat('-', 125) . PHP_EOL;
}

try {
    $options = parseOptions(array_slice($argv, 1));
    $baseUrl = validateBaseUrl($options['base-url']);
    $paths = parsePaths($options['paths']);
    $requestsPerLevel = positiveInteger(
        $options['requests'],
        'requests'
    );
    $concurrencyLevels = parseConcurrencyLevels(
        $options['concurrency']
    );
    $warmupPerPath = nonNegativeInteger($options['warmup'], 'warmup');
    $timeout = positiveInteger($options['timeout'], 'timeout');
    $p95TargetMs = nonNegativeFloat(
        $options['p95-target-ms'],
        'p95-target-ms'
    );
    $errorTargetPercent = nonNegativeFloat(
        $options['error-target-percent'],
        'error-target-percent'
    );

    if ($requestsPerLevel > MAX_REQUESTS_PER_LEVEL) {
        throw new InvalidArgumentException(
            '--requests no puede superar ' . MAX_REQUESTS_PER_LEVEL . '.'
        );
    }

    if ($warmupPerPath > MAX_WARMUP_PER_PATH) {
        throw new InvalidArgumentException(
            '--warmup no puede superar ' . MAX_WARMUP_PER_PATH . '.'
        );
    }

    if ($timeout > MAX_TIMEOUT_SECONDS) {
        throw new InvalidArgumentException(
            '--timeout no puede superar ' . MAX_TIMEOUT_SECONDS . ' segundos.'
        );
    }

    if ($errorTargetPercent > 100) {
        throw new InvalidArgumentException(
            '--error-target-percent no puede superar 100.'
        );
    }

    $totalRequests = $requestsPerLevel * count($concurrencyLevels);
    $totalWarmups = $warmupPerPath * count($paths) * count($concurrencyLevels);

    if ($totalRequests + $totalWarmups > MAX_TOTAL_REQUESTS) {
        throw new InvalidArgumentException(
            'La cantidad total de solicitudes medidas y de calentamiento no puede superar '
            . MAX_TOTAL_REQUESTS . '.'
        );
    }

    $projectRoot = dirname(__DIR__);
    $reportDirectory = $projectRoot
        . DIRECTORY_SEPARATOR
        . 'storage'
        . DIRECTORY_SEPARATOR
        . 'stress-tests';

    if (
        !is_dir($reportDirectory)
        && !mkdir($reportDirectory, 0775, true)
        && !is_dir($reportDirectory)
    ) {
        throw new RuntimeException(
            "No se pudo crear el directorio de resultados: {$reportDirectory}"
        );
    }

    $scenarios = [];

    foreach ($concurrencyLevels as $concurrency) {
        echo "Ejecutando nivel de concurrencia {$concurrency}..." . PHP_EOL;

        $warmupPaths = [];

        for ($round = 0; $round < $warmupPerPath; $round++) {
            foreach ($paths as $path) {
                $warmupPaths[] = $path;
            }
        }

        $warmupResult = $warmupPaths === []
            ? ['samples' => [], 'elapsed_seconds' => 0.0]
            : sendRequests(
                $baseUrl,
                $warmupPaths,
                $concurrency,
                $timeout
            );

        $requestPaths = [];

        for ($request = 0; $request < $requestsPerLevel; $request++) {
            $requestPaths[] = $paths[$request % count($paths)];
        }

        $measuredResult = sendRequests(
            $baseUrl,
            $requestPaths,
            $concurrency,
            $timeout
        );
        $overall = summarizeSamples(
            $measuredResult['samples'],
            $measuredResult['elapsed_seconds'],
            $p95TargetMs,
            $errorTargetPercent
        );

        $scenarios[] = [
            'concurrency' => $concurrency,
            'warmup' => summarizeSamples(
                $warmupResult['samples'],
                $warmupResult['elapsed_seconds'],
                $p95TargetMs,
                $errorTargetPercent
            ),
            'overall' => $overall,
            'by_path' => summarizeByPath(
                $measuredResult['samples'],
                $measuredResult['elapsed_seconds'],
                $p95TargetMs,
                $errorTargetPercent
            ),
        ];
    }

    $resultAssessment = overallAssessment($scenarios);
    $report = [
        'project_snapshot' => projectSnapshot($projectRoot),
        'test' => [
            'base_url' => $baseUrl,
            'paths' => $paths,
            'requests_per_concurrency_level' => $requestsPerLevel,
            'concurrency_levels' => $concurrencyLevels,
            'warmup_requests_per_path_and_level' => $warmupPerPath,
            'timeout_seconds' => $timeout,
            'method' => 'GET',
            'authenticated' => getenv('STRESS_TEST_BEARER_TOKEN') !== false
                && getenv('STRESS_TEST_BEARER_TOKEN') !== '',
            'writes_business_data' => false,
            'performance_targets' => [
                'p95_latency_ms' => $p95TargetMs,
                'max_error_percent' => $errorTargetPercent,
                'note' => 'Targets orientativos configurables, no un SLA ni una garantía de producción.',
            ],
            'measurement_notes' => [
                'latency_scope' => 'Tiempo total observado desde el cliente hasta recibir la respuesta completa.',
                'not_measured' => [
                    'CPU o memoria del servidor',
                    'tiempo exclusivo de base de datos',
                    'capacidad de producción o de múltiples servidores',
                ],
                'rate_limiting' => 'HTTP 429 se informa por separado y vuelve inconcluso el nivel, ya que el limitador puede impedir medir la capacidad real.',
                'small_samples' => 'Menos de 30 solicitudes por ruta y nivel se marca como provisional; para conclusiones más estables se recomienda repetir.',
            ],
        ],
        'result' => $resultAssessment,
        'scenarios' => $scenarios,
    ];

    $filenameBase = 'stress-test-'
        . date('Ymd-His')
        . '-'
        . bin2hex(random_bytes(3));
    $jsonPath = $reportDirectory
        . DIRECTORY_SEPARATOR
        . $filenameBase
        . '.json';
    $csvPath = $reportDirectory
        . DIRECTORY_SEPARATOR
        . $filenameBase
        . '.csv';
    $json = json_encode(
        $report,
        JSON_PRETTY_PRINT
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_THROW_ON_ERROR
    );

    if (file_put_contents($jsonPath, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException(
            "No se pudo guardar el reporte JSON: {$jsonPath}"
        );
    }

    writeCsvReport($report, $csvPath);
    printScenarioSummary($scenarios);
    echo "Objetivos orientativos: p95 <= {$p95TargetMs} ms; errores <= "
        . "{$errorTargetPercent}%." . PHP_EOL;
    echo "Reporte JSON: {$jsonPath}" . PHP_EOL;
    echo "Resumen CSV: {$csvPath}" . PHP_EOL;
    echo "Resultado general: {$resultAssessment}" . PHP_EOL;
    echo "Los datos solo describen esta carga local; no son una garantía de producción."
        . PHP_EOL;

    exit(
        str_starts_with($resultAssessment, 'REVISAR:')
            ? 1
            : (str_starts_with($resultAssessment, 'INCONCLUSO:') ? 2 : 0)
    );
} catch (Throwable $exception) {
    fwrite(STDERR, 'Error: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
