<?php

namespace App\Repositories;

use App\Models\Provincia;
use Illuminate\Database\Eloquent\Collection;

interface ProvinciaRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Provincia;

    public function findDeletedById(int $id): ?Provincia;

    public function existsByName(
        string $nombre,
        ?int $exceptId = null
    ): bool;

    public function hasLocalities(Provincia $provincia): bool;

    public function create(array $data): Provincia;

    public function update(Provincia $provincia, array $data): bool;

    public function delete(Provincia $provincia): bool;

    public function restore(Provincia $provincia): bool;
}