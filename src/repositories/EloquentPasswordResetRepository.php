<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PasswordReset;

class EloquentPasswordResetRepository implements PasswordResetRepositoryInterface
{
    public function create(array $data): int
    {
        return PasswordReset::create($data)->id;
    }

    public function findByTokenAndEmail(
        string $token,
        string $email
    ): ?PasswordReset {
        return PasswordReset::query()
            ->where('token', $token)
            ->where('email', $email)
            ->first();
    }

    public function marcarUsado(int $id): bool
    {
        return (bool) PasswordReset::query()
            ->whereKey($id)
            ->update(['usado' => 1]);
    }
}