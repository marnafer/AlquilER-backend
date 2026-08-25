<?php

namespace App\Repositories;
use Illuminate\Database\Eloquent\Collection;

use App\Models\Usuario;

interface UsuarioRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Usuario;

    public function create(array $data): Usuario;

    public function update(Usuario $usuario, array $data): bool;

    public function delete(Usuario $usuario): bool;

    public function restore(Usuario $usuario): bool;

    public function findDeletedById(int $id): ?Usuario;

    public function existsByEmail(string $email, ?int $exceptId = null): bool;

    public function findByEmail(string $email): ?Usuario;

    public function createWithRole(array $data, int $roleId): Usuario;
}