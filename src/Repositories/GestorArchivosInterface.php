<?php

declare(strict_types=1);

namespace App\Repositories;

interface GestorArchivosInterface
{
    public function upload(array $file, string $directory): string;

    public function delete(string $path): void;
}