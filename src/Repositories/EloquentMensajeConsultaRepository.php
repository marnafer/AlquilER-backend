<?php

namespace App\Repositories;

use App\Models\MensajeConsulta;
use Illuminate\Database\Eloquent\Collection;

class EloquentMensajeConsultaRepository implements MensajeConsultaRepositoryInterface
{
    public function create(array $data): MensajeConsulta
    {
        return MensajeConsulta::create($data);
    }

    public function findByConsultaId(int $consultaId): Collection
    {
        return MensajeConsulta::with('usuario')
            ->where('consulta_id', $consultaId)
            ->orderBy('fecha_mensaje', 'asc')
            ->get();
    }

    public function findById(int $id): ?MensajeConsulta
    {
        return MensajeConsulta::find($id);
    }

    public function findLatestByConsultaId(
        int $consultaId,
        int $limit
    ): Collection {
        return MensajeConsulta::with('usuario')
            ->where('consulta_id', $consultaId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function findOlderByConsultaId(
        int $consultaId,
        int $beforeId,
        int $limit
    ): Collection {
        return MensajeConsulta::with('usuario')
            ->where('consulta_id', $consultaId)
            ->where('id', '<', $beforeId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function findNewerByConsultaId(
        int $consultaId,
        int $afterId,
        int $limit
    ): Collection {
        return MensajeConsulta::with('usuario')
            ->where('consulta_id', $consultaId)
            ->where('id', '>', $afterId)
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();
    }

    public function update(int $id, array $data): bool
    {
        $mensaje = MensajeConsulta::find($id);

        if (!$mensaje) {
            return false;
        }

        return $mensaje->update($data);
    }

    public function delete(int $id): void
    {
        MensajeConsulta::destroy($id);
    }
}