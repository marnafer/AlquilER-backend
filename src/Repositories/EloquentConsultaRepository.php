<?php

namespace App\Repositories;

use App\Models\Consulta;
use App\Models\MensajeConsulta;

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
     * Obtener consultas por usuario, ordenadas por actividad reciente.
     */
    public function getByUsuario(int $usuarioId): array
    {
        $query = Consulta::where('usuario_id', $usuarioId)
            ->with(['propiedad', 'propiedad.imagenes']);

        $this->agregarUltimoMensaje($query);

        return $query
            ->orderByRaw(
                'COALESCE(ultima_actividad, consultas.fecha_consulta) DESC'
            )
            ->orderByDesc('consultas.id')
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

    /**
     * Buscar una consulta activa por propiedad e interesado.
     */
    public function findByPropiedadYUsuario(int $propiedadId, int $usuarioId): ?Consulta {
        return Consulta::where('propiedad_id', $propiedadId)
            ->where('usuario_id', $usuarioId)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Obtener consultas recibidas en propiedades del usuario,
     * ordenadas por actividad reciente.
     */
    public function getRecibidasPorPropietario(int $usuarioId): array
    {
        $query = Consulta::whereHas(
            'propiedad',
            function ($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            }
        )->with(['propiedad', 'usuario']);

        $this->agregarUltimoMensaje($query);

        return $query
            ->orderByRaw(
                'COALESCE(ultima_actividad, consultas.fecha_consulta) DESC'
            )
            ->orderByDesc('consultas.id')
            ->get()
            ->toArray();
    }

    /**
     * Agregar la fecha y el texto del último mensaje no eliminado.
     */
    private function agregarUltimoMensaje($query): void
    {
        $query->addSelect([
            'ultimo_mensaje' => MensajeConsulta::query()
                ->select('mensaje')
                ->whereColumn(
                    'mensajes_consultas.consulta_id',
                    'consultas.id'
                )
                ->orderByDesc('fecha_mensaje')
                ->orderByDesc('id')
                ->limit(1),

            'ultima_actividad' => MensajeConsulta::query()
                ->select('fecha_mensaje')
                ->whereColumn(
                    'mensajes_consultas.consulta_id',
                    'consultas.id'
                )
                ->orderByDesc('fecha_mensaje')
                ->orderByDesc('id')
                ->limit(1),
        ]);
    }
}