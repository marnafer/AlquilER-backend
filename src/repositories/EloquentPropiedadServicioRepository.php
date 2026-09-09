<?php

namespace App\Repositories;

use App\Models\PropiedadServicio;
use Illuminate\Support\Facades\DB;

class EloquentPropiedadServicioRepository implements PropiedadServicioRepositoryInterface
{
    /**
     * Obtener todos los servicios de una propiedad
     */
    public function getByPropiedad(int $propiedadId): array
    {
        return PropiedadServicio::where('propiedad_id', $propiedadId)
            ->with(['servicio'])
            ->get()
            ->toArray();
    }
    
    /**
     * Obtener todas las propiedades de un servicio
     */
    public function getByServicio(int $servicioId): array
    {
        return PropiedadServicio::where('servicio_id', $servicioId)
            ->with(['propiedad'])
            ->get()
            ->toArray();
    }
    
    /**
     * Verificar si una propiedad tiene un servicio
     */
    public function exists(int $propiedadId, int $servicioId): bool
    {
        return PropiedadServicio::where('propiedad_id', $propiedadId)
            ->where('servicio_id', $servicioId)
            ->exists();
    }
    
    /**
     * Asignar un servicio a una propiedad
     */
    public function attach(int $propiedadId, int $servicioId): bool
    {
        if ($this->exists($propiedadId, $servicioId)) {
            return false;
        }
        
        return PropiedadServicio::create([
            'propiedad_id' => $propiedadId,
            'servicio_id' => $servicioId
        ]) ? true : false;
    }
    
    /**
     * Desasignar un servicio de una propiedad
     */
    public function detach(int $propiedadId, int $servicioId): bool
    {
        return PropiedadServicio::where('propiedad_id', $propiedadId)
            ->where('servicio_id', $servicioId)
            ->delete() > 0;
    }
    
    /**
     * Asignar múltiples servicios a una propiedad
     */
    public function attachMultiple(int $propiedadId, array $servicioIds): array
    {
        $resultados = [
            'asignados' => [],
            'duplicados' => [],
            'errores' => []
        ];
        
        foreach ($servicioIds as $servicioId) {
            try {
                if ($this->attach($propiedadId, $servicioId)) {
                    $resultados['asignados'][] = $servicioId;
                } else {
                    $resultados['duplicados'][] = $servicioId;
                }
            } catch (\Exception $e) {
                $resultados['errores'][] = [
                    'servicio_id' => $servicioId,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $resultados;
    }
    
    /**
     * Sincronizar servicios de una propiedad (reemplaza todos)
     */
    public function sync(int $propiedadId, array $servicioIds): array
    {
        // Obtener servicios actuales
        $actuales = $this->getServicioIdsByPropiedad($propiedadId);
        
        // Calcular diferencias
        $paraAgregar = array_diff($servicioIds, $actuales);
        $paraEliminar = array_diff($actuales, $servicioIds);
        
        $resultados = [
            'agregados' => [],
            'eliminados' => [],
            'mantenidos' => array_intersect($servicioIds, $actuales)
        ];
        
        // Agregar nuevos
        foreach ($paraAgregar as $servicioId) {
            if ($this->attach($propiedadId, $servicioId)) {
                $resultados['agregados'][] = $servicioId;
            }
        }
        
        // Eliminar los que no están
        foreach ($paraEliminar as $servicioId) {
            if ($this->detach($propiedadId, $servicioId)) {
                $resultados['eliminados'][] = $servicioId;
            }
        }
        
        return $resultados;
    }
    
    /**
     * Obtener IDs de servicios de una propiedad
     */
    public function getServicioIdsByPropiedad(int $propiedadId): array
    {
        return PropiedadServicio::where('propiedad_id', $propiedadId)
            ->pluck('servicio_id')
            ->toArray();
    }
}