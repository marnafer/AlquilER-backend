<?php

namespace App\Repositories;

use App\Models\Rol;
use Illuminate\Database\Eloquent\Collection;

interface RolRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Rol;

    public function existsByName(
        string $nombre,
        ?int $exceptId = null
    ): bool;

    public function hasUsers(Rol $rol): bool;

    public function create(array $data): Rol;

    public function update(Rol $rol, array $data): bool;

    public function delete(Rol $rol): bool;

    public function findDeletedById(int $id): ?Rol;

    public function restore(Rol $rol): bool;
}
