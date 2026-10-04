<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\OperacionArchivosException;
use App\Repositories\PropiedadImagenRepositoryInterface;

final class LimpiadorArchivosLocales implements LimpiadorArchivosInterface
{
    private const EDAD_MINIMA_SEGUNDOS = 86400;

    private const EXTENSIONES_PERMITIDAS = [
        'jpg',
        'png',
    ];

    public function __construct(
        private readonly PropiedadImagenRepositoryInterface $repository
    ) {
    }

    public function limpiar(
        string $directorio,
        bool $ejecutar = false
    ): array {
        if (!is_dir($directorio)) {
            throw new OperacionArchivosException(
                'El directorio de imágenes no existe'
            );
        }

        $resultado = [
            'archivos_analizados' => 0,
            'archivos_referenciados' => 0,
            'archivos_huerfanos' => 0,
            'archivos_eliminados' => 0,
            'archivos_referenciados_inexistentes' => 0,
        ];

        $rutasReferenciadas = [];

        $this->repository->recorrerRutas(
            function (string $ruta) use (
                &$rutasReferenciadas,
                &$resultado
            ): void {
                $rutaNormalizada = $this->normalizarRuta($ruta);

                $rutasReferenciadas[$rutaNormalizada] = true;

                $resultado['archivos_referenciados']++;
            }
        );

        $archivos = scandir($directorio);

        if ($archivos === false) {
            throw new OperacionArchivosException(
                'No se pudo leer el directorio de imágenes'
            );
        }

        $rutasEncontradas = [];

        foreach ($archivos as $archivo) {
            if ($archivo === '.' || $archivo === '..') {
                continue;
            }

            $rutaFisica = $directorio
                . DIRECTORY_SEPARATOR
                . $archivo;

            if (!is_file($rutaFisica)) {
                continue;
            }

            $extension = strtolower(
                pathinfo($archivo, PATHINFO_EXTENSION)
            );

            if (!in_array(
                $extension,
                self::EXTENSIONES_PERMITIDAS,
                true
            )) {
                continue;
            }

            $resultado['archivos_analizados']++;

            $rutaRelativa = '/uploads/propiedades/' . $archivo;

            $rutaNormalizada = $this->normalizarRuta(
                $rutaRelativa
            );

            $rutasEncontradas[$rutaNormalizada] = true;

            if (isset($rutasReferenciadas[$rutaNormalizada])) {
                continue;
            }

            if (!$this->esAntiguo($rutaFisica)) {
                continue;
            }

            $resultado['archivos_huerfanos']++;

            if (!$ejecutar) {
                continue;
            }

            if (!unlink($rutaFisica)) {
                throw new OperacionArchivosException(
                    'No se pudo eliminar el archivo: ' . $rutaFisica
                );
            }

            $resultado['archivos_eliminados']++;
        }

        foreach ($rutasReferenciadas as $ruta => $_) {
            if (!isset($rutasEncontradas[$ruta])) {
                $resultado['archivos_referenciados_inexistentes']++;
            }
        }

        return $resultado;
    }

    private function normalizarRuta(string $ruta): string
    {
        return '/' . ltrim(
            str_replace('\\', '/', $ruta),
            '/'
        );
    }

    private function esAntiguo(string $ruta): bool
    {
        $fechaModificacion = filemtime($ruta);

        if ($fechaModificacion === false) {
            return false;
        }

        return (
            time() - $fechaModificacion
        ) >= self::EDAD_MINIMA_SEGUNDOS;
    }
}