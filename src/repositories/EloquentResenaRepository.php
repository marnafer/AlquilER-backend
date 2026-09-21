<?php

namespace App\Repositories;

use App\Models\Resena;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentResenaRepository implements ResenaRepositoryInterface
{
    public function all(array $filtros = []): Collection
    {
        $query = Resena::query()
            ->with([
                'reserva',
                'reserva.propiedad',
                'reserva.usuario',
                'calificador',
            ]);

        if (!empty($filtros['solo_eliminados'])) {
            $query->onlyTrashed();
        } elseif (!empty($filtros['incluir_eliminados'])) {
            $query->withTrashed();
        }

        if (!empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (isset($filtros['calificacion'])) {
            $query->where(
                'calificacion',
                $filtros['calificacion']
            );
        }

        if (isset($filtros['calificacion_min'])) {
            $query->where(
                'calificacion',
                '>=',
                $filtros['calificacion_min']
            );
        }

        if (isset($filtros['calificacion_max'])) {
            $query->where(
                'calificacion',
                '<=',
                $filtros['calificacion_max']
            );
        }

        if (!empty($filtros['reserva_id'])) {
            $query->where(
                'reserva_id',
                $filtros['reserva_id']
            );
        }

        if (!empty($filtros['calificador_id'])) {
            $query->where(
                'calificador_id',
                $filtros['calificador_id']
            );
        }

        if (!empty($filtros['propiedad_id'])) {
            $query->whereHas(
                'reserva',
                function (Builder $query) use ($filtros): void {
                    $query->where(
                        'propiedad_id',
                        $filtros['propiedad_id']
                    );
                }
            );
        }

        if (!empty($filtros['usuario_id'])) {
            $query->whereHas(
                'reserva',
                function (Builder $query) use ($filtros): void {
                    $query->where(
                        'usuario_id',
                        $filtros['usuario_id']
                    );
                }
            );
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where(
                'fecha_publicacion',
                '>=',
                $filtros['fecha_desde']
            );
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where(
                'fecha_publicacion',
                '<=',
                $filtros['fecha_hasta']
            );
        }

        return $query
            ->orderByDesc('fecha_publicacion')
            ->get();
    }

    public function findById(int $id): ?Resena
    {
        return Resena::query()
            ->with([
                'reserva',
                'reserva.propiedad',
                'reserva.usuario',
                'calificador',
            ])
            ->whereKey($id)
            ->first();
    }

    public function findDeletedById(int $id): ?Resena
    {
        return Resena::onlyTrashed()
            ->with([
                'reserva',
                'reserva.propiedad',
                'reserva.usuario',
                'calificador',
            ])
            ->whereKey($id)
            ->first();
    }

    public function create(array $data): Resena
    {
        return Resena::create($data);
    }

    public function update(Resena $resena, array $data): bool
    {
        return $resena->update($data);
    }

    public function delete(Resena $resena): bool
    {
        return (bool) $resena->delete();
    }

    public function restore(Resena $resena): bool
    {
        return (bool) $resena->restore();
    }

    public function getByReserva(int $reservaId): Collection
    {
        return Resena::query()
            ->where('reserva_id', $reservaId)
            ->with([
                'reserva',
                'reserva.propiedad',
                'reserva.usuario',
                'calificador',
            ])
            ->orderByDesc('fecha_publicacion')
            ->get();
    }

    public function getByPropiedad(int $propiedadId): Collection
    {
        return Resena::query()
            ->where('tipo', 'propiedad')
            ->whereHas(
                'reserva',
                function (Builder $query) use ($propiedadId): void {
                    $query->where(
                        'propiedad_id',
                        $propiedadId
                    );
                }
            )
            ->with([
                'reserva',
                'reserva.usuario',
                'calificador',
            ])
            ->orderByDesc('fecha_publicacion')
            ->get();
    }

    public function getByUsuario(int $usuarioId): Collection
    {
        return Resena::query()
            ->where('tipo', 'inquilino')
            ->whereHas(
                'reserva',
                function (Builder $query) use ($usuarioId): void {
                    $query->where(
                        'usuario_id',
                        $usuarioId
                    );
                }
            )
            ->with([
                'reserva',
                'reserva.propiedad',
                'calificador',
            ])
            ->orderByDesc('fecha_publicacion')
            ->get();
    }

    public function getByCalificador(int $calificadorId): Collection
    {
        return Resena::query()
            ->where(
                'calificador_id',
                $calificadorId
            )
            ->with([
                'reserva',
                'reserva.propiedad',
                'reserva.usuario',
                'calificador',
            ])
            ->orderByDesc('fecha_publicacion')
            ->get();
    }

    public function getPromedioByPropiedad(int $propiedadId): float
    {
        return (float) (
            Resena::query()
                ->where('tipo', 'propiedad')
                ->whereHas(
                    'reserva',
                    function (Builder $query) use ($propiedadId): void {
                        $query->where(
                            'propiedad_id',
                            $propiedadId
                        );
                    }
                )
                ->avg('calificacion') ?? 0
        );
    }

    public function getPromedioByUsuario(int $usuarioId): float
    {
        return (float) (
            Resena::query()
                ->where('tipo', 'inquilino')
                ->whereHas(
                    'reserva',
                    function (Builder $query) use ($usuarioId): void {
                        $query->where(
                            'usuario_id',
                            $usuarioId
                        );
                    }
                )
                ->avg('calificacion') ?? 0
        );
    }

    public function existePorReservaYTipo(
        int $reservaId,
        string $tipo
    ): bool {
        return Resena::query()
            ->where('reserva_id', $reservaId)
            ->where('tipo', $tipo)
            ->exists();
    }
}