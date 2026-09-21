<?php

namespace App\Repositories;

use App\Models\Propiedad;
use Illuminate\Database\Eloquent\Collection;

class EloquentPropiedadRepository implements PropiedadRepositoryInterface
{
    public function all(): Collection
    {
        return Propiedad::query()
            ->with(['imagenes', 'imagenPrincipal'])
            ->orderBy('id', 'asc')
            ->get();
    }

    public function porUsuario(int $usuarioId): Collection
    {
        return Propiedad::query()
            ->with(['imagenes', 'imagenPrincipal'])
            ->where('usuario_id', $usuarioId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function findById(int $id): ?Propiedad
    {
        return Propiedad::query()
            ->with(['imagenes', 'imagenPrincipal'])
            ->whereKey($id)
            ->first();
    }

    public function findDeletedById(int $id): ?Propiedad
    {
        return Propiedad::onlyTrashed()->find($id);
    }

    public function create(array $data): Propiedad
    {
        return Propiedad::create($data);
    }

    public function update(Propiedad $propiedad, array $data): bool
    {
        return $propiedad->update($data);
    }

    public function delete(Propiedad $propiedad): bool
    {
        return (bool) $propiedad->delete();
    }

    public function restore(Propiedad $propiedad): bool
    {
        return (bool) $propiedad->restore();
    }

    public function findByIdForUpdate(int $id): ?Propiedad
    {
        return Propiedad::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }
}