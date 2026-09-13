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

    public function delete(int $id): void
    {
        MensajeConsulta::destroy($id);
    }
}