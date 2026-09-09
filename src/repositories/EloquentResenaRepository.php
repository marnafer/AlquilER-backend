<?php

namespace App\Repositories;

use App\Models\Resena;
use Illuminate\Database\Eloquent\Builder;

class EloquentResenaRepository implements ResenaRepositoryInterface
{
    public function getAll(array $filtros = []): array
    {
        $query = Resena::with([
            'reserva',
            'reserva.propiedad',
            'reserva.usuario',
            'calificado',
            'calificador'
        ]);
        
        // Filtros
        if (!empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }
        
        if (!empty($filtros['calificacion'])) {
            $query->where('calificacion', $filtros['calificacion']);
        }
        
        if (!empty($filtros['calificacion_min'])) {
            $query->where('calificacion', '>=', $filtros['calificacion_min']);
        }
        
        if (!empty($filtros['calificacion_max'])) {
            $query->where('calificacion', '<=', $filtros['calificacion_max']);
        }
        
        if (!empty($filtros['calificado_id'])) {
            $query->where('calificado_id', $filtros['calificado_id']);
        }
        
        if (!empty($filtros['calificador_id'])) {
            $query->where('calificador_id', $filtros['calificador_id']);
        }
        
        if (!empty($filtros['reserva_id'])) {
            $query->where('reserva_id', $filtros['reserva_id']);
        }
        
        if (!empty($filtros['propiedad_id'])) {
            $query->whereHas('reserva', function (Builder $q) use ($filtros) {
                $q->where('propiedad_id', $filtros['propiedad_id']);
            });
        }
        
        if (!empty($filtros['usuario_id'])) {
            $query->whereHas('reserva', function (Builder $q) use ($filtros) {
                $q->where('usuario_id', $filtros['usuario_id']);
            });
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_publicacion', '>=', $filtros['fecha_desde']);
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_publicacion', '<=', $filtros['fecha_hasta']);
        }
        
        if (!empty($filtros['incluir_eliminados']) && $filtros['incluir_eliminados'] === true) {
            $query->withTrashed();
        }
        
        if (!empty($filtros['solo_eliminados']) && $filtros['solo_eliminados'] === true) {
            $query->onlyTrashed();
        }
        
        $query->orderBy('fecha_publicacion', 'desc');
        
        return $query->get()->toArray();
    }
    
    public function findById(int $id)
    {
        return Resena::with([
            'reserva',
            'reserva.propiedad',
            'reserva.usuario',
            'calificado',
            'calificador'
        ])->withTrashed()->find($id);
    }
    
    public function create(array $data): int
    {
        $resena = Resena::create($data);
        return $resena->id;
    }
    
    public function update(int $id, array $data): bool
    {
        $resena = Resena::find($id);
        if (!$resena) {
            return false;
        }
        return $resena->update($data);
    }
    
    public function delete(int $id): bool
    {
        $resena = Resena::find($id);
        if (!$resena) {
            return false;
        }
        return $resena->delete();
    }
    
    public function restore(int $id): bool
    {
        $resena = Resena::withTrashed()->find($id);
        if (!$resena) {
            return false;
        }
        return $resena->restore();
    }
    
    public function getByReserva(int $reservaId): array
    {
        return Resena::where('reserva_id', $reservaId)
            ->with(['calificado', 'calificador'])
            ->get()
            ->toArray();
    }
    
    public function getByPropiedad(int $propiedadId): array
    {
        return Resena::dePropiedad()
            ->porPropiedad($propiedadId)
            ->with(['calificador', 'reserva.usuario'])
            ->orderBy('fecha_publicacion', 'desc')
            ->get()
            ->toArray();
    }
    
    public function getByUsuario(int $usuarioId): array
    {
        return Resena::deInquilino()
            ->where('calificado_id', $usuarioId)
            ->with(['calificador', 'reserva.propiedad'])
            ->orderBy('fecha_publicacion', 'desc')
            ->get()
            ->toArray();
    }
    
    public function getByCalificador(int $calificadorId): array
    {
        return Resena::where('calificador_id', $calificadorId)
            ->with(['calificado', 'reserva'])
            ->orderBy('fecha_publicacion', 'desc')
            ->get()
            ->toArray();
    }
    
    public function getPromedioByPropiedad(int $propiedadId): float
    {
        return Resena::dePropiedad()
            ->porPropiedad($propiedadId)
            ->avg('calificacion') ?? 0.0;
    }
    
    public function getPromedioByUsuario(int $usuarioId): float
    {
        return Resena::deInquilino()
            ->where('calificado_id', $usuarioId)
            ->avg('calificacion') ?? 0.0;
    }
    
    public function existePorReservaYTipo(int $reservaId, string $tipo): bool
    {
        return Resena::where('reserva_id', $reservaId)
            ->where('tipo', $tipo)
            ->exists();
    }
    
    public function existePorReserva(int $reservaId): bool
    {
        return Resena::where('reserva_id', $reservaId)->exists();
    }
}