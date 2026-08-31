<?php

namespace App\Repositories;

use App\Models\Localidad;
use Illuminate\Database\Eloquent\Collection;

class EloquentLocalidadRepository implements LocalidadRepositoryInterface
{
    public function all(): Collection
    {
        return Localidad::query()
            ->orderByDesc('id')
            ->get();
    }

    public function findById(int $id): ?Localidad
    {
        return Localidad::query()
            ->whereKey($id)
            ->first();
    }

    public function findDeletedById(int $id): ?Localidad
    {
        return Localidad::onlyTrashed()->find($id);
    }

    public function existsByNameInProvince(
        string $nombre,
        int $provinciaId,
        ?int $exceptId = null
    ): bool {
        $query = Localidad::query()
            ->where('nombre', $nombre)
            ->where('provincia_id', $provinciaId);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function hasProperties(Localidad $localidad): bool
    {
        return $localidad->propiedades()->exists();
    }

    public function create(array $data): Localidad
    {
        return Localidad::create($data);
    }

    public function update(Localidad $localidad, array $data): bool
    {
        return $localidad->update($data);
    }

    public function delete(Localidad $localidad): bool
    {
        return (bool) $localidad->delete();
    }

    public function restore(Localidad $localidad): bool
    {
        return (bool) $localidad->restore();
    }
}
