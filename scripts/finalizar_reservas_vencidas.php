<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);

/*
|--------------------------------------------------------------------------
| Cargar variables de entorno
|--------------------------------------------------------------------------
*/

$dotenv = Dotenv\Dotenv::createImmutable($basePath);
$dotenv->safeLoad();

/*
|--------------------------------------------------------------------------
| Configuración
|--------------------------------------------------------------------------
*/

require_once $basePath . '/src/database.php';
require_once $basePath . '/config/config.php';

date_default_timezone_set(
    'America/Argentina/Buenos_Aires'
);

try {
    $bootstrap = require $basePath . '/src/bootstrap.php';

    $reservaService = $bootstrap['reservaService'];

    $cantidad = $reservaService->finalizarVencidas();

    echo "Reservas finalizadas automáticamente: {$cantidad}" . PHP_EOL;

    exit(0);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;

    exit(1);
}