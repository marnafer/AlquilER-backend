<?php

namespace App\Validators;

interface ImageUploadValidatorInterface
{
    public function validate(array $file): ?string;
}