<?php

namespace App\Repositories;

use App\Models\Localidad;
use Illuminate\Database\Eloquent\Collection;

interface LocalidadRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Localidad;

    public function findDeletedById(int $id): ?Localidad;

    public function existsByNameInProvince(
        string $nombre,
        int $provinciaId,
        ?int $exceptId = null
    ): bool;

    public function hasProperties(Localidad $localidad): bool;

    public function create(array $data): Localidad;

    public function update(Localidad $localidad, array $data): bool;

    public function delete(Localidad $localidad): bool;

    public function restore(Localidad $localidad): bool;
}
