<?php

namespace App\Repositories;

use App\Models\Servicio;
use Illuminate\Support\Collection;

interface ServicioRepositoryInterface
{
    public function all(array $filtros = []): Collection;

    public function findById(int $id): ?Servicio;

    public function findDeletedById(int $id): ?Servicio;

    public function findByIds(array $ids): Collection;

    public function existsByName(
        string $nombre,
        ?int $exceptId = null
    ): bool;

    public function hasProperties(Servicio $servicio): bool;

    public function create(array $data): Servicio;

    public function update(
        Servicio $servicio,
        array $data
    ): bool;

    public function delete(Servicio $servicio): bool;

    public function restore(Servicio $servicio): bool;
}