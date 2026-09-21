<?php

namespace App\Repositories;

use App\Models\PropiedadServicio;

class EloquentPropiedadServicioRepository
    implements PropiedadServicioRepositoryInterface
{
    public function getByPropiedad(int $propiedadId): array
    {
        return PropiedadServicio::query()
            ->where('propiedad_id', $propiedadId)
            ->with('servicio')
            ->get()
            ->toArray();
    }

    public function getByServicio(int $servicioId): array
    {
        return PropiedadServicio::query()
            ->where('servicio_id', $servicioId)
            ->with('propiedad')
            ->get()
            ->toArray();
    }

    public function exists(
        int $propiedadId,
        int $servicioId
    ): bool {
        return PropiedadServicio::query()
            ->where('propiedad_id', $propiedadId)
            ->where('servicio_id', $servicioId)
            ->exists();
    }

    public function attach(
        int $propiedadId,
        int $servicioId
    ): bool {
        if ($this->exists($propiedadId, $servicioId)) {
            return false;
        }

        return PropiedadServicio::query()->create([
            'propiedad_id' => $propiedadId,
            'servicio_id' => $servicioId,
        ]) !== null;
    }

    public function detach(
        int $propiedadId,
        int $servicioId
    ): bool {
        return PropiedadServicio::query()
            ->where('propiedad_id', $propiedadId)
            ->where('servicio_id', $servicioId)
            ->delete() > 0;
    }

    public function attachMultiple(
        int $propiedadId,
        array $servicioIds
    ): array {
        $resultados = [
            'asignados' => [],
            'duplicados' => [],
            'errores' => [],
        ];

        foreach ($servicioIds as $servicioId) {
            if ($this->attach($propiedadId, $servicioId)) {
                $resultados['asignados'][] = $servicioId;
            } else {
                $resultados['duplicados'][] = $servicioId;
            }
        }

        return $resultados;
    }

    public function sync(
        int $propiedadId,
        array $servicioIds
    ): array {
        $actuales = $this->getServicioIdsByPropiedad(
            $propiedadId
        );

        $paraAgregar = array_values(
            array_diff($servicioIds, $actuales)
        );

        $paraEliminar = array_values(
            array_diff($actuales, $servicioIds)
        );

        $mantenidos = array_values(
            array_intersect($servicioIds, $actuales)
        );

        $resultados = [
            'agregados' => [],
            'eliminados' => [],
            'mantenidos' => $mantenidos,
        ];

        foreach ($paraAgregar as $servicioId) {
            if ($this->attach($propiedadId, $servicioId)) {
                $resultados['agregados'][] = $servicioId;
            }
        }

        foreach ($paraEliminar as $servicioId) {
            if ($this->detach($propiedadId, $servicioId)) {
                $resultados['eliminados'][] = $servicioId;
            }
        }

        return $resultados;
    }

    public function getServicioIdsByPropiedad(
        int $propiedadId
    ): array {
        return PropiedadServicio::query()
            ->where('propiedad_id', $propiedadId)
            ->pluck('servicio_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}