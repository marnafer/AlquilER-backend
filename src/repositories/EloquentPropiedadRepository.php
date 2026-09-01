<?php

namespace App\Repositories;

use App\Models\Propiedad;
use Illuminate\Database\Eloquent\Collection;

class EloquentPropiedadRepository implements PropiedadRepositoryInterface
{
    public function all(): Collection
    {
        return Propiedad::query()
            ->orderBy('id', 'asc')
            ->get();
    }

    public function findById(int $id): ?Propiedad
    {
        return Propiedad::query()
            ->whereKey($id)
            ->first();
    }

    public function findDeletedById(int $id): ?Propiedad
    {
        return Propiedad::query()
            ->withTrashed()
            ->whereKey($id)
            ->first();
    }

    public function create(array $data): Propiedad
    {
        return Propiedad::create($data);
    }

    public function update(Propiedad $propiedad, array $data): bool
    {
        return $propiedad->update($data);
    }

    public function delete(Propiedad $propiedad): bool
    {
        return (bool) $propiedad->delete();
    }

    public function restore(Propiedad $propiedad): bool
    {
        return (bool) $propiedad->restore();
    }
}
