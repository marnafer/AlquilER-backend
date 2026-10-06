<?php

namespace App\Repositories;

use App\Models\RefreshToken;

class EloquentRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function create(array $data): RefreshToken
    {
        return RefreshToken::create($data);
    }

    public function findValidByToken(string $token): ?RefreshToken
    {
        $tokenHash = hash('sha256', $token);

        return RefreshToken::where('token', $tokenHash)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();
    }

    public function deleteByToken(string $token): void
    {
        $tokenHash = hash('sha256', $token);

        RefreshToken::where('token', $tokenHash)->delete();
    }

    public function deleteById(int $id): void
    {
        RefreshToken::destroy($id);
    }

    public function deleteByUsuarioId(int $usuarioId): int
    {
        return RefreshToken::where('usuario_id', $usuarioId)->delete();
    }
}