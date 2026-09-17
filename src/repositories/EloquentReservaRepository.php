<?php

namespace App\Repositories;

use App\Models\Reserva;

class EloquentReservaRepository implements ReservaRepositoryInterface
{
    public function getAll(array $filtros = []): array
    {
        $query = Reserva::with([
            'propiedad',
            'usuario'
        ]);

        if (!empty($filtros['usuario_id'])) {
            $query->where(
                'usuario_id',
                $filtros['usuario_id']
            );
        }

        if (!empty($filtros['propiedad_id'])) {
            $query->where(
                'propiedad_id',
                $filtros['propiedad_id']
            );
        }

        if (!empty($filtros['estado'])) {
            $query->where(
                'estado',
                $filtros['estado']
            );
        }

        if (
            !empty($filtros['incluir_eliminados']) &&
            $filtros['incluir_eliminados'] === true
        ) {
            $query->withTrashed();
        }

        if (
            !empty($filtros['solo_eliminados']) &&
            $filtros['solo_eliminados'] === true
        ) {
            $query->onlyTrashed();
        }

        return $query
            ->orderBy('fecha_reserva', 'desc')
            ->get()
            ->toArray();
    }

    public function findById(int $id)
    {
        return Reserva::with([
            'propiedad',
            'usuario',
            'resenas'
        ])
            ->withTrashed()
            ->find($id);
    }

    public function create(array $data): int
    {
        $reserva = Reserva::create($data);

        return $reserva->id;
    }

    public function update(int $id, array $data): bool
    {
        $reserva = Reserva::find($id);

        if (!$reserva) {
            return false;
        }

        return $reserva->update($data);
    }

    public function delete(int $id): bool
    {
        $reserva = Reserva::find($id);

        if (!$reserva) {
            return false;
        }

        return $reserva->delete();
    }

    public function restore(int $id): bool
    {
        $reserva = Reserva::withTrashed()->find($id);

        if (!$reserva) {
            return false;
        }

        return $reserva->restore();
    }

    public function getByUsuario(int $usuarioId): array
    {
        return Reserva::with([
            'propiedad',
            'propiedad.imagenes'
        ])
            ->where('usuario_id', $usuarioId)
            ->orderBy('fecha_reserva', 'desc')
            ->get()
            ->toArray();
    }

    public function getByPropiedad(int $propiedadId): array
    {
        return Reserva::with([
            'usuario'
        ])
            ->where('propiedad_id', $propiedadId)
            ->orderBy('fecha_reserva', 'desc')
            ->get()
            ->toArray();
    }
}