<?php

namespace App\Repositories;

use App\Models\Servicio;
use Illuminate\Support\Collection;

class EloquentServicioRepository implements ServicioRepositoryInterface
{
    public function all(): Collection
    {
        return Servicio::query()
            ->orderBy('id', 'asc')
            ->get();
    }

    public function findById(int $id): ?Servicio
    {
        return Servicio::query()
            ->whereKey($id)
            ->first();
    }

    public function findDeletedById(int $id): ?Servicio
    {
        return Servicio::query()
            ->onlyTrashed()
            ->whereKey($id)
            ->first();
    }

    public function findByIds(array $ids): Collection
    {
        return Servicio::query()
            ->whereIn('id', $ids)
            ->get();
    }

    public function existsByName(
        string $nombre,
        ?int $exceptId = null
    ): bool {
        $query = Servicio::query()
            ->where('nombre', $nombre);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function hasProperties(Servicio $servicio): bool
    {
        return $servicio->propiedades()->exists();
    }

    public function create(array $data): Servicio
    {
        return Servicio::create($data);
    }

    public function update(
        Servicio $servicio,
        array $data
    ): bool {
        return $servicio->update($data);
    }

    public function delete(Servicio $servicio): bool
    {
        return (bool) $servicio->delete();
    }

    public function restore(Servicio $servicio): bool
    {
        return (bool) $servicio->restore();
    }
}