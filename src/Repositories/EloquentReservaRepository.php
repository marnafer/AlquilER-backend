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

    public function findDeletedById(int $id)
    {
        return Reserva::onlyTrashed()->find($id);
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

    /**
     * Todas las reservas dentro del alcance del usuario, en una sola consulta.
     *
     * El alcance es "las que él mismo hizo, más las que otros hicieron sobre
     * sus propiedades", y los filtros solo lo acotan. Nunca lo amplían: pedir
     * una propiedad ajena devuelve solo las reservas propias sobre esa
     * propiedad, jamás las de otros.
     */
    public function listarPorAlcance(
        int $usuarioId,
        array $propiedadIds,
        array $filtros = []
    ): array {
        $propiedadIds = array_values(
            array_filter(
                array_map('intval', $propiedadIds),
                static fn (int $id): bool => $id > 0
            )
        );

        // Superset de lo que cargaban getByUsuario() y getByPropiedad() por
        // separado: esas cargaban propiedad.imagenes en un caso y usuario en el
        // otro, dejando cada reserva con un shape distinto.
        $query = Reserva::with([
            'propiedad',
            'propiedad.imagenes',
            'usuario',
        ]);

        $query->where(function ($q) use ($usuarioId, $propiedadIds) {
            $q->where('usuario_id', $usuarioId);

            if (!empty($propiedadIds)) {
                $q->orWhereIn('propiedad_id', $propiedadIds);
            }
        });

        // El filtro de propiedad es un where aparte, no una acotacion de
        // $propiedadIds. Acotar no alcanza: la rama usuario_id del alcance
        // seguiria metiendo reservas de otras propiedades.
        if (!empty($filtros['propiedad_id'])) {
            $query->where(
                'propiedad_id',
                (int) $filtros['propiedad_id']
            );
        }

        if (!empty($filtros['usuario_id'])) {
            $query->where(
                'usuario_id',
                (int) $filtros['usuario_id']
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

    public function tieneReservaActiva(int $propiedadId): bool
    {
        return Reserva::query()
            ->where('propiedad_id', $propiedadId)
            ->whereIn('estado', [
                'pendiente',
                'confirmada'
            ])
            ->where(function ($query) {
                $query
                    ->whereNull('fecha_fin_alquiler')
                    ->orWhereDate(
                        'fecha_fin_alquiler',
                        '>=',
                        date('Y-m-d')
                    );
            })
            ->exists();
    }

    public function hayReservaConfirmadaSolapada(
        int $propiedadId,
        string $fechaInicio,
        ?string $fechaFin = null,
        ?int $excluirId = null
    ): bool {
        return Reserva::query()
            ->where('propiedad_id', $propiedadId)
            ->where('estado', 'confirmada')
            ->when(
                $excluirId !== null,
                fn ($query) => $query->where('id', '!=', $excluirId)
            )
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                if ($fechaFin === null) {
                    // Rango abierto: choca con toda reserva que empiece
                    // despues de nuestro inicio.
                    $query->where('fecha_inicio_alquiler', '>=', $fechaInicio);

                    return;
                }

                // Solape inclusivo: la otra reserva arranca antes de que
                // termine nuestro rango y termina despues de que empiece.
                $query
                    ->where('fecha_inicio_alquiler', '<=', $fechaFin)
                    ->where(function ($query) use ($fechaInicio) {
                        $query
                            ->whereNull('fecha_fin_alquiler')
                            ->orWhere(
                                'fecha_fin_alquiler',
                                '>=',
                                $fechaInicio
                            );
                    });
            })
            ->exists();
    }

    public function finalizarVencidas(): int
    {
        return Reserva::query()
            ->whereIn('estado', [
                'pendiente',
                'confirmada'
            ])
            ->whereNotNull('fecha_fin_alquiler')
            ->whereDate(
                'fecha_fin_alquiler',
                '<',
                date('Y-m-d')
            )
            ->whereNull('deleted_at')
            ->update([
                'estado' => 'finalizada'
            ]);
    }
}