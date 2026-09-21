<?php

declare(strict_types=1);

namespace App\Services;

interface LimpiadorArchivosInterface
{
    public function limpiar(
        string $directorio,
        bool $ejecutar = false
    ): array;
}