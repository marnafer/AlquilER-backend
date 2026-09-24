<?php

namespace App\Repositories;

use App\Models\Propiedad;
use Illuminate\Database\Eloquent\Collection;

class EloquentPropiedadRepository implements PropiedadRepositoryInterface
{
    public function all(array $filtros = []): Collection
    {
        $query = Propiedad::query()
            ->with(['imagenes', 'imagenPrincipal']);

        if (isset($filtros['categoria_id'])) {
            $query->whereIn(
                'categoria_id',
                $filtros['categoria_id']
            );
        }

        if (isset($filtros['localidad_id'])) {
            $query->whereIn(
                'localidad_id',
                $filtros['localidad_id']
            );
        }

        return $query
            ->orderBy('id', 'asc')
            ->get();
    }

    public function allParaAdmin(array $filtros = []): Collection
    {
        $query = Propiedad::query()
            ->with([
                'usuario',
                'categoria',
                'localidad',
                'imagenes',
                'imagenPrincipal'
            ]);

        if (!empty($filtros['solo_eliminados'])) {
            $query->onlyTrashed();
        } elseif (!empty($filtros['incluir_eliminados'])) {
            $query->withTrashed();
        }

        return $query
            ->orderByDesc('id')
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

    public function update(
        Propiedad $propiedad,
        array $data
    ): bool {
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