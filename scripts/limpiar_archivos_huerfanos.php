<?php

declare(strict_types=1);

use App\Exceptions\OperacionArchivosException;
use App\Repositories\EloquentPropiedadImagenRepository;
use App\Services\LimpiadorArchivosLocales;

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
| Configuración de base de datos
|--------------------------------------------------------------------------
*/

require_once $basePath . '/src/database.php';

/*
|--------------------------------------------------------------------------
| Directorio de imágenes
|--------------------------------------------------------------------------
*/

$directorio = $basePath . '/public/uploads/propiedades';

/*
|--------------------------------------------------------------------------
| Modo de ejecución
|--------------------------------------------------------------------------
*/

$ejecutar = in_array(
    '--ejecutar',
    $argv,
    true
);

echo PHP_EOL;
echo "========================================" . PHP_EOL;
echo " Limpieza de archivos de propiedades" . PHP_EOL;
echo "========================================" . PHP_EOL;
echo PHP_EOL;

if ($ejecutar) {
    echo "MODO: EJECUCIÓN" . PHP_EOL;
    echo "Los archivos huérfanos serán eliminados." . PHP_EOL;
} else {
    echo "MODO: SIMULACIÓN" . PHP_EOL;
    echo "No se eliminará ningún archivo." . PHP_EOL;
}

echo PHP_EOL;

try {
    $repository = new EloquentPropiedadImagenRepository();

    $limpiador = new LimpiadorArchivosLocales(
        $repository
    );

    $resultado = $limpiador->limpiar(
        $directorio,
        $ejecutar
    );

    echo "Resultado:" . PHP_EOL;
    echo PHP_EOL;

    echo "  Archivos analizados: "
        . $resultado['archivos_analizados']
        . PHP_EOL;

    echo "  Archivos referenciados: "
        . $resultado['archivos_referenciados']
        . PHP_EOL;

    echo "  Archivos huérfanos: "
        . $resultado['archivos_huerfanos']
        . PHP_EOL;

    echo "  Archivos eliminados: "
        . $resultado['archivos_eliminados']
        . PHP_EOL;

    echo "  Referencias sin archivo: "
        . $resultado['archivos_referenciados_inexistentes']
        . PHP_EOL;

    echo PHP_EOL;

    if (!$ejecutar) {
        echo "Simulación finalizada. ";
        echo "Ejecute con --ejecutar para eliminar los archivos."
        . PHP_EOL;
    } else {
        echo "Limpieza finalizada correctamente."
        . PHP_EOL;
    }

    echo PHP_EOL;

    exit(0);
} catch (OperacionArchivosException $e) {
    echo PHP_EOL;
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo PHP_EOL;

    exit(1);
} catch (Throwable $e) {
    echo PHP_EOL;
    echo "ERROR inesperado: " . $e->getMessage() . PHP_EOL;
    echo PHP_EOL;

    exit(1);
}