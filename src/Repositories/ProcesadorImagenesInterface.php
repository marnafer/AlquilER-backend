<?php

declare(strict_types=1);

namespace App\Repositories;

interface ProcesadorImagenesInterface
{
    /**
     * Procesa una imagen subida y devuelve la ruta temporal
     * del JPEG optimizado.
     */
    public function procesar(array $file): string;
}
