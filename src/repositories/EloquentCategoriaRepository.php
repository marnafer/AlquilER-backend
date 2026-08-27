<?php

namespace App\Repositories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Collection;

Class EloquentCategoriaRepository implements CategoriaRepositoryInterface   
{
    public function all(): Collection
    {
        return Categoria::query()
            ->orderByDesc('id')
            ->get();
    }

    public function findById(int $id): ?Categoria
    {
        return Categoria::query()
            ->whereKey($id)
            ->first();
    }

    public function findDeletedById(int $id): ?Categoria
    {
        return Categoria::onlyTrashed()->find($id);
    }

    public function existsByName(string $nombre, ?int $exceptId = null): bool
    {
        $query = Categoria::query()
            ->where('nombre', $nombre);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function hasProperties(Categoria $categoria): bool
    {
        return $categoria->propiedades()->exists();
    }

    public function create(array $data): Categoria
    {
        return Categoria::create($data);
    }

    public function update(Categoria $categoria, array $data): bool
    {
        return $categoria->update($data);
    }

    public function delete(Categoria $categoria): bool
    {
        return (bool) $categoria->delete();
    }

    public function restore(Categoria $categoria): bool
    {
        return (bool) $categoria->restore();
    }
}