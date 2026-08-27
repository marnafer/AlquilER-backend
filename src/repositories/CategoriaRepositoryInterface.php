<?php

namespace App\Repositories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Collection;

interface CategoriaRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Categoria;

    public function findDeletedById(int $id): ?Categoria;

    public function existsByName(
        string $nombre,
        ?int $exceptId = null
    ): bool;

    public function hasProperties(Categoria $categoria): bool;

    public function create(array $data): Categoria;

    public function update(Categoria $categoria, array $data): bool;

    public function delete(Categoria $categoria): bool;

    public function restore(Categoria $categoria): bool;
}