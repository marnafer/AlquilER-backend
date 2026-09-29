<?php

namespace App\Repositories;

use App\Models\Propiedad;
use Illuminate\Database\Eloquent\Collection;

class EloquentPropiedadRepository implements PropiedadRepositoryInterface
{
    public function all(array $filtros = []): Collection
    {
        $query = Propiedad::query()
            ->with(['imagenes', 'imagenPrincipal', 'servicios']);

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

        if (!empty($filtros['servicio_id'])) {
        foreach ($filtros['servicio_id'] as $servicioId) {
            $query->whereExists(
                function ($subquery) use ($servicioId) {
                    $subquery
                        ->selectRaw('1')
                        ->from('propiedad_servicio')
                        ->whereColumn(
                            'propiedad_servicio.propiedad_id',
                            'propiedades.id'
                        )
                        ->where(
                            'propiedad_servicio.servicio_id',
                            $servicioId
                        );
                }
            );
        }
    }

        if (isset($filtros['precio_min'])) {
            $query->where(
                'precio',
                '>=',
                $filtros['precio_min']
            );
        }

        if (isset($filtros['precio_max'])) {
            $query->where(
                'precio',
                '<=',
                $filtros['precio_max']
            );
        }

        if (isset($filtros['cantidad_ambientes'])) {
            $query->where(
                'cantidad_ambientes',
                '>=',
                $filtros['cantidad_ambientes']
            );
        }

        if (isset($filtros['cantidad_dormitorios'])) {
            $query->where(
                'cantidad_dormitorios',
                '>=',
                $filtros['cantidad_dormitorios']
            );
        }

        if (isset($filtros['cantidad_banos'])) {
            $query->where(
                'cantidad_banos',
                '>=',
                $filtros['cantidad_banos']
            );
        }

        if (isset($filtros['capacidad'])) {
            $query->where(
                'capacidad',
                '>=',
                $filtros['capacidad']
            );
        }

        if (isset($filtros['destacada'])) {
            $query->where(
                'destacada',
                (int) $filtros['destacada']
            );
        }

        if (isset($filtros['disponible'])) {
            $query->where(
                'disponible',
                (int) $filtros['disponible']
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
        // servicios viene eager para que el panel del propietario no tenga que
        // pedir /propiedades/{id}/servicios una vez por cada propiedad (N+1).
        return Propiedad::query()
            ->with(['imagenes', 'imagenPrincipal', 'servicios'])
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