<?php

declare(strict_types=1);

use App\Performance\PerformanceMetricsQueryListener;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;

$capsule = new Capsule();

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

$metricsEnabled =
    strtolower(trim((string) ($_ENV['APP_ENV'] ?? 'production'))) === 'development'
    && strtolower(
        trim((string) ($_ENV['PERFORMANCE_METRICS_ENABLED'] ?? 'false'))
    ) === 'true';

if ($metricsEnabled) {
    $dispatcher = new Dispatcher(new Container());
    $queryListener = new PerformanceMetricsQueryListener();

    $dispatcher->listen(
        QueryExecuted::class,
        static function (QueryExecuted $event) use ($queryListener): void {
            $queryListener->handle($event);
        }
    );

    $capsule->setEventDispatcher($dispatcher);
}