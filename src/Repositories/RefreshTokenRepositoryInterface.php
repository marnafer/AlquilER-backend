<?php

namespace App\Repositories;

use App\Models\RefreshToken;

interface RefreshTokenRepositoryInterface
{
    public function create(array $data): RefreshToken;
    
    public function findValidByToken(string $token): ?RefreshToken;
    
    public function deleteByToken(string $token): void;
    
    public function deleteById(int $id): void;

    public function deleteByUsuarioId(int $usuarioId): int;
    
}