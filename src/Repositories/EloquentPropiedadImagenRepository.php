<?php

namespace App\Repositories;

use App\Models\PropiedadImagen;
use Illuminate\Database\Eloquent\Collection;

class EloquentPropiedadImagenRepository implements PropiedadImagenRepositoryInterface
{
    public function all(): Collection
    {
        return PropiedadImagen::query()
            ->with('propiedad')
            ->orderByDesc('id')
            ->get();
    }

    public function findById(int $id): ?PropiedadImagen
    {
        return PropiedadImagen::query()
            ->with('propiedad')
            ->whereKey($id)
            ->first();
    }

    public function findByPropiedadId(int $propiedadId): Collection
    {
        return PropiedadImagen::query()
            ->where('propiedad_id', $propiedadId)
            ->orderByDesc('id')
            ->get();
    }

    public function countByPropiedadId(int $propiedadId): int
    {
        return PropiedadImagen::query()
            ->where('propiedad_id', $propiedadId)
            ->count();
    }

    public function create(array $data): PropiedadImagen
    {
        return PropiedadImagen::create($data);
    }

    public function clearPrincipalByPropiedadId(int $propiedadId): void
    {
        PropiedadImagen::query()
            ->where('propiedad_id', $propiedadId)
            ->update([
                'es_principal' => 0
            ]);
    }

    public function setPrincipal(PropiedadImagen $imagen): void
    {
        $imagen->update([
            'es_principal' => 1
        ]);
    }

    public function delete(PropiedadImagen $imagen): void
    {
        $imagen->delete();
    }

    public function recorrerRutas(callable $callback): void
    {
        PropiedadImagen::query()
            ->select(['id', 'ruta'])
            ->orderBy('id')
            ->chunkById(
                1000,
                function (Collection $imagenes) use ($callback): void {
                    foreach ($imagenes as $imagen) {
                        $callback($imagen->ruta);
                    }
                }
            );
    }
}