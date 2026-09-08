<?php

declare(strict_types=1);

namespace App\Services;

interface FileUploaderInterface
{
    public function upload(array $file, string $directory): string;
}