<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PasswordReset;

interface PasswordResetRepositoryInterface
{
    public function create(array $data): int;

    public function findByTokenAndEmail(
        string $token,
        string $email
    ): ?PasswordReset;

    public function marcarUsado(int $id): bool;
}