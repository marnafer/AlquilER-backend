<?php

namespace App\Repositories;

use App\Models\Rol;
use Illuminate\Database\Eloquent\Collection;

class EloquentRolRepository implements RolRepositoryInterface
{
    public function all(): Collection
    {
        return Rol::query()
            ->orderBy('id', 'asc')
            ->get();
    }

    public function findById(int $id): ?Rol
    {
        return Rol::query()
            ->whereKey($id)
            ->first();
    }

    public function existsByName(string $nombre, ?int $exceptId = null): bool
    {
        $query = Rol::query()
            ->where('nombre', $nombre);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function hasUsers(Rol $rol): bool
    {
        return $rol->usuarios()->exists();
    }

    public function create(array $data): Rol
    {
        return Rol::create($data);
    }

    public function update(Rol $rol, array $data): bool
    {
        return $rol->update($data);
    }

    public function delete(Rol $rol): bool
    {
        return (bool) $rol->delete();
    }

    public function findDeletedById(int $id): ?Rol
    {
        return Rol::onlyTrashed()->find($id);
    }

    public function restore(Rol $rol): bool
    {
        return (bool) $rol->restore();
    }
}
