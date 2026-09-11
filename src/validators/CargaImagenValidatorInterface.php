<?php

namespace App\Validators;

interface CargaImagenValidatorInterface
{
    public function validate(array $file): ?string;
}