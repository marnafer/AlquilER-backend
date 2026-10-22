<?php

declare(strict_types=1);

namespace App\Repositories;

interface GestorArchivosInterface
{
    public function upload(string $rutaTemporal, string $directory): string;

    public function delete(string $path): void;
}
