<?php

namespace App\Repositories;

use App\Models\Consulta;

class EloquentConsultaRepository implements ConsultaRepositoryInterface
{
    /**
     * Obtener todas las consultas con filtros
     */
    public function getAll(array $filtros = []): array
    {
        $query = Consulta::with(['propiedad', 'usuario']);
        
        if (!empty($filtros['usuario_id'])) {
            $query->where('usuario_id', $filtros['usuario_id']);
        }
        
        if (!empty($filtros['propiedad_id'])) {
            $query->where('propiedad_id', $filtros['propiedad_id']);
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_consulta', '>=', $filtros['fecha_desde']);
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_consulta', '<=', $filtros['fecha_hasta']);
        }
        
        if (!empty($filtros['incluir_eliminados']) && $filtros['incluir_eliminados'] === true) {
            $query->withTrashed();
        }
        
        if (!empty($filtros['solo_eliminados']) && $filtros['solo_eliminados'] === true) {
            $query->onlyTrashed();
        }
        
        $query->orderBy('fecha_consulta', 'desc');
        
        return $query->get()->toArray();
    }
    
    /**
     * Buscar una consulta por ID
     */
    public function findById(int $id)
    {
        return Consulta::with(['propiedad', 'usuario'])
            ->withTrashed()
            ->find($id);
    }
    
    /**
     * Crear una nueva consulta
     */
    public function create(array $data): int
    {
        $consulta = Consulta::create($data);
        return $consulta->id;
    }
    
    /**
     * Actualizar una consulta
     */
    public function update(int $id, array $data): bool
    {
        $consulta = Consulta::find($id);
        if (!$consulta) {
            return false;
        }
        return $consulta->update($data);
    }
    
    /**
     * Eliminar una consulta (soft delete)
     */
    public function delete(int $id): bool
    {
        $consulta = Consulta::find($id);
        if (!$consulta) {
            return false;
        }
        return $consulta->delete();
    }
    
    /**
     * Restaurar una consulta eliminada
     */
    public function restore(int $id): bool
    {
        $consulta = Consulta::withTrashed()->find($id);
        if (!$consulta) {
            return false;
        }
        return $consulta->restore();
    }
    
    /**
     * Obtener consultas por usuario
     */
    public function getByUsuario(int $usuarioId): array
    {
        return Consulta::where('usuario_id', $usuarioId)
            ->with(['propiedad', 'propiedad.imagenes'])
            ->orderBy('fecha_consulta', 'desc')
            ->get()
            ->toArray();
    }
    
    /**
     * Obtener consultas por propiedad
     */
    public function getByPropiedad(int $propiedadId): array
    {
        return Consulta::where('propiedad_id', $propiedadId)
            ->with(['usuario'])
            ->orderBy('fecha_consulta', 'desc')
            ->get()
            ->toArray();
    }
}