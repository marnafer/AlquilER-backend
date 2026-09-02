<?php

namespace App\Repositories;

use App\Models\LogActividad;
use Illuminate\Support\Collection;

class EloquentLogActividadRepository implements LogActividadRepositoryInterface
{
    public function all(): Collection
    {
        return LogActividad::query()
            ->with('usuario')
            ->orderByDesc('fecha')
            ->get()
            ->map(fn (LogActividad $log): array => $this->transform($log));
    }

    public function findById(int $id): ?array
    {
        $log = LogActividad::query()
            ->with('usuario')
            ->whereKey($id)
            ->first();

        return $log ? $this->transform($log) : null;
    }
    
    public function create(array $data): LogActividad
    {
        return LogActividad::create($data);
    }


    private function transform(LogActividad $log): array
    {
        return [
            'id' => $log->id,
            'usuario_id' => $log->usuario_id,
            'accion' => $log->accion,
            'ip_address' => $log->ip_address,
            'fecha' => $log->fecha,
            'usuario_nombre' => $log->usuario
                ? $log->usuario->nombre . ' ' . $log->usuario->apellido
                : null,
            'usuario_email' => $log->usuario->email ?? null,
        ];
    }
}
