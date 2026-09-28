<?php

namespace App\Repositories;

use App\Models\Provincia;
use Illuminate\Database\Eloquent\Collection;

class EloquentProvinciaRepository implements ProvinciaRepositoryInterface
{
    public function all(array $filtros = []): Collection
    {
        $query = Provincia::query();

        if (!empty($filtros['solo_eliminados'])) {
            $query->onlyTrashed();
        } elseif (!empty($filtros['incluir_eliminados'])) {
            $query->withTrashed();
        }

        return $query
            ->orderBy('nombre')
            ->get();
    }

    public function findById(int $id): ?Provincia
    {
        return Provincia::query()
            ->whereKey($id)
            ->first();
    }

    public function findDeletedById(int $id): ?Provincia
    {
        return Provincia::onlyTrashed()->find($id);
    }

    public function existsByName(
        string $nombre,
        ?int $exceptId = null
    ): bool {
        $query = Provincia::query()
            ->where('nombre', $nombre);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function hasLocalities(Provincia $provincia): bool
    {
        return $provincia->localidades()->exists();
    }

    public function create(array $data): Provincia
    {
        return Provincia::create($data);
    }

    public function update(Provincia $provincia, array $data): bool
    {
        return $provincia->update($data);
    }

    public function delete(Provincia $provincia): bool
    {
        return (bool) $provincia->delete();
    }

    public function restore(Provincia $provincia): bool
    {
        return (bool) $provincia->restore();
    }
}