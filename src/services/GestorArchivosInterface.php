<?php

declare(strict_types=1);

namespace App\Services;

interface GestorArchivosInterface
{
    public function upload(array $file, string $directory): string;
}