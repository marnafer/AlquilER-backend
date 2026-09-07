<?php

namespace App\Repositories;

use App\Models\Reserva;
use Illuminate\Database\Eloquent\Builder;

class EloquentReservaRepository implements ReservaRepositoryInterface
{
    /**
     * Obtener todas las reservas con filtros
     */
    public function getAll(array $filtros = []): array
    {
        $query = Reserva::with(['propiedad', 'usuario']);
        
        // Filtrar por usuario
        if (!empty($filtros['usuario_id'])) {
            $query->where('usuario_id', $filtros['usuario_id']);
        }
        
        // Filtrar por propiedad
        if (!empty($filtros['propiedad_id'])) {
            $query->where('propiedad_id', $filtros['propiedad_id']);
        }
        
        // Filtrar por estado
        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        
        // Filtrar por rango de fechas
        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $query->where(function (Builder $q) use ($filtros) {
                $q->whereBetween('fecha_inicio_alquiler', [$filtros['fecha_inicio'], $filtros['fecha_fin']])
                  ->orWhereBetween('fecha_fin_alquiler', [$filtros['fecha_inicio'], $filtros['fecha_fin']]);
            });
        }
        
        // Incluir eliminados
        if (!empty($filtros['incluir_eliminados']) && $filtros['incluir_eliminados'] === true) {
            $query->withTrashed();
        }
        
        // Solo eliminados
        if (!empty($filtros['solo_eliminados']) && $filtros['solo_eliminados'] === true) {
            $query->onlyTrashed();
        }
        
        return $query->orderBy('fecha_reserva', 'desc')->get()->toArray();
    }
    
    /**
     * Buscar una reserva por ID
     */
    public function findById(int $id)
    {
        return Reserva::with(['propiedad', 'usuario', 'resena'])
            ->withTrashed()
            ->find($id);
    }
    
    /**
     * Crear una nueva reserva
     */
    public function create(array $data): int
    {
        $reserva = Reserva::create($data);
        return $reserva->id;
    }
    
    /**
     * Actualizar una reserva
     */
    public function update(int $id, array $data): bool
    {
        $reserva = Reserva::find($id);
        if (!$reserva) {
            return false;
        }
        return $reserva->update($data);
    }
    
    /**
     * Eliminar una reserva (soft delete)
     */
    public function delete(int $id): bool
    {
        $reserva = Reserva::find($id);
        if (!$reserva) {
            return false;
        }
        return $reserva->delete();
    }
    
    /**
     * Restaurar una reserva eliminada
     */
    public function restore(int $id): bool
    {
        $reserva = Reserva::withTrashed()->find($id);
        if (!$reserva) {
            return false;
        }
        return $reserva->restore();
    }
    
    /**
     * Obtener reservas por usuario
     */
    public function getByUsuario(int $usuarioId): array
    {
        return Reserva::with(['propiedad', 'propiedad.imagenes'])
            ->where('usuario_id', $usuarioId)
            ->orderBy('fecha_reserva', 'desc')
            ->get()
            ->toArray();
    }
    
    /**
     * Obtener reservas por propiedad
     */
    public function getByPropiedad(int $propiedadId): array
    {
        return Reserva::with(['usuario'])
            ->where('propiedad_id', $propiedadId)
            ->orderBy('fecha_inicio_alquiler', 'desc')
            ->get()
            ->toArray();
    }
    
    /**
     * Verificar disponibilidad de una propiedad en un rango de fechas
     */
    public function isAvailable(int $propiedadId, string $fechaInicio, string $fechaFin): bool
    {
        // Buscar reservas confirmadas que se superpongan
        $reservas = Reserva::where('propiedad_id', $propiedadId)
            ->where('estado', 'confirmada')
            ->where(function (Builder $q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_inicio_alquiler', [$fechaInicio, $fechaFin])
                  ->orWhereBetween('fecha_fin_alquiler', [$fechaInicio, $fechaFin])
                  ->orWhere(function ($sub) use ($fechaInicio, $fechaFin) {
                      $sub->where('fecha_inicio_alquiler', '<=', $fechaInicio)
                          ->where('fecha_fin_alquiler', '>=', $fechaFin);
                  });
            })
            ->exists();
        
        return !$reservas;
    }
    
    /**
     * Cambiar estado de una reserva
     */
    public function cambiarEstado(int $id, string $estado): bool
    {
        $reserva = Reserva::find($id);
        if (!$reserva) {
            return false;
        }
        
        $estadosValidos = ['pendiente', 'confirmada', 'rechazada', 'cancelada', 'finalizada'];
        if (!in_array($estado, $estadosValidos)) {
            return false;
        }
        
        $reserva->estado = $estado;
        return $reserva->save();
    }
}