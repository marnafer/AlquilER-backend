<?php

namespace App\Repositories;

use App\Models\Servicio;
use Illuminate\Database\Eloquent\Collection;

interface ServicioRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Servicio;

    public function findDeletedById(int $id): ?Servicio;

    public function existsByName(
        string $nombre,
        ?int $exceptId = null
    ): bool;

    public function hasProperties(Servicio $servicio): bool;

    public function create(array $data): Servicio;

    public function update(Servicio $servicio, array $data): bool;

    public function delete(Servicio $servicio): bool;

    public function restore(Servicio $servicio): bool;
}