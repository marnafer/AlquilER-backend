<?php

namespace App\Repositories;

use App\Models\Provincia;
use Illuminate\Database\Eloquent\Collection;

class EloquentProvinciaRepository implements ProvinciaRepositoryInterface
{
    public function all(): Collection
    {
        return Provincia::all();
    }

    public function findById(int $id): ?Provincia
    {
        return Provincia::find($id);
    }

    public function findDeletedById(int $id): ?Provincia
    {
        return Provincia::onlyTrashed()->find($id);
    }

    public function existsByName(string $nombre, ?int $exceptId = null): bool
    {
        $query = Provincia::where('nombre', $nombre);

        if ($exceptId !== null) {
            $query = $query->where('id', '!=', $exceptId);
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
        return $provincia->delete();
    }

    public function restore(Provincia $provincia): bool
    {
        return $provincia->restore();
    }
}