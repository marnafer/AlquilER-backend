<?php

namespace App\Repositories;

use App\Models\Resena;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Builder;

class EloquentResenaRepository implements ResenaRepositoryInterface
{
    /**
     * Obtener todas las reseñas con filtros
     */
    public function getAll(array $filtros = []): array
    {
        $query = Resena::with(['reserva', 'reserva.propiedad', 'reserva.usuario']);
        
        // Filtros
        if (!empty($filtros['calificacion'])) {
            $query->where('calificacion', $filtros['calificacion']);
        }
        
        if (!empty($filtros['calificacion_min'])) {
            $query->where('calificacion', '>=', $filtros['calificacion_min']);
        }
        
        if (!empty($filtros['calificacion_max'])) {
            $query->where('calificacion', '<=', $filtros['calificacion_max']);
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
    
    /**
     * Buscar una reseña por ID
     */
    public function findById(int $id)
    {
        return Resena::with(['reserva', 'reserva.propiedad', 'reserva.usuario'])
            ->withTrashed()
            ->find($id);
    }
    
    /**
     * Crear una nueva reseña
     */
    public function create(array $data): int
    {
        $resena = Resena::create($data);
        return $resena->id;
    }
    
    /**
     * Actualizar una reseña
     */
    public function update(int $id, array $data): bool
    {
        $resena = Resena::find($id);
        if (!$resena) {
            return false;
        }
        return $resena->update($data);
    }
    
    /**
     * Eliminar una reseña (soft delete)
     */
    public function delete(int $id): bool
    {
        $resena = Resena::find($id);
        if (!$resena) {
            return false;
        }
        return $resena->delete();
    }
    
    /**
     * Restaurar una reseña eliminada
     */
    public function restore(int $id): bool
    {
        $resena = Resena::withTrashed()->find($id);
        if (!$resena) {
            return false;
        }
        return $resena->restore();
    }
    
    /**
     * Obtener reseñas por reserva
     */
    public function getByReserva(int $reservaId): array
    {
        return Resena::where('reserva_id', $reservaId)
            ->with(['reserva.propiedad', 'reserva.usuario'])
            ->get()
            ->toArray();
    }
    
    /**
     * Obtener promedio de calificación por propiedad
     */
    public function getPromedioByPropiedad(int $propiedadId): float
    {
        return Resena::whereHas('reserva', function (Builder $q) use ($propiedadId) {
                $q->where('propiedad_id', $propiedadId);
            })
            ->avg('calificacion') ?? 0.0;
    }
    
    /**
     * Obtener reseñas por propiedad (a través de reservas)
     */
    public function getByPropiedad(int $propiedadId): array
    {
        return Resena::whereHas('reserva', function (Builder $q) use ($propiedadId) {
                $q->where('propiedad_id', $propiedadId);
            })
            ->with(['reserva.usuario'])
            ->orderBy('fecha_publicacion', 'desc')
            ->get()
            ->toArray();
    }
    
    /**
     * Verificar si una reserva ya tiene reseña
     */
    public function existePorReserva(int $reservaId): bool
    {
        return Resena::where('reserva_id', $reservaId)->exists();
    }
}