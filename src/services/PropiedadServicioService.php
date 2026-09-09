<?php

namespace App\Services;

use App\Repositories\PropiedadServicioRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\ServicioRepositoryInterface;
use App\Services\LogActividadService;

class PropiedadServicioService
{
    private PropiedadServicioRepositoryInterface $propiedadServicioRepository;
    private PropiedadRepositoryInterface $propiedadRepository;
    private ServicioRepositoryInterface $servicioRepository;
    private LogActividadService $logService;
    
    public function __construct(
        PropiedadServicioRepositoryInterface $propiedadServicioRepository,
        PropiedadRepositoryInterface $propiedadRepository,
        ServicioRepositoryInterface $servicioRepository,
        LogActividadService $logService
    ) {
        $this->propiedadServicioRepository = $propiedadServicioRepository;
        $this->propiedadRepository = $propiedadRepository;
        $this->servicioRepository = $servicioRepository;
        $this->logService = $logService;
    }
    
    /**
     * Obtener servicios de una propiedad
     */
    public function obtenerServiciosPorPropiedad(int $propiedadId): array
    {
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        
        return $this->propiedadServicioRepository->getByPropiedad($propiedadId);
    }
    
    /**
     * Obtener propiedades de un servicio
     */
    public function obtenerPropiedadesPorServicio(int $servicioId): array
    {
        $servicio = $this->servicioRepository->findById($servicioId);
        if (!$servicio) {
            throw new \Exception("El servicio no existe", 404);
        }
        
        return $this->propiedadServicioRepository->getByServicio($servicioId);
    }
    
    /**
     * Verificar si una propiedad tiene un servicio
     */
    public function tieneServicio(int $propiedadId, int $servicioId): bool
    {
        return $this->propiedadServicioRepository->exists($propiedadId, $servicioId);
    }
    
    /**
     * Asignar un servicio a una propiedad
     */
    public function asignarServicio(int $propiedadId, int $servicioId, int $usuarioId): bool
    {
        // Validar que la propiedad existe
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        
        // Validar que el servicio existe
        $servicio = $this->servicioRepository->findById($servicioId);
        if (!$servicio) {
            throw new \Exception("El servicio no existe", 404);
        }
        
        // Intentar asignar
        $resultado = $this->propiedadServicioRepository->attach($propiedadId, $servicioId);
        
        if ($resultado) {
            $this->logService->registrar(
                'servicio_asignado',
                "Usuario {$usuarioId} asignó servicio {$servicioId} a propiedad {$propiedadId}",
                $usuarioId
            );
        }
        
        return $resultado;
    }
    
    /**
     * Desasignar un servicio de una propiedad
     */
    public function desasignarServicio(int $propiedadId, int $servicioId, int $usuarioId): bool
    {
        // Verificar que existe la relación
        if (!$this->propiedadServicioRepository->exists($propiedadId, $servicioId)) {
            throw new \Exception("La propiedad no tiene este servicio asignado", 404);
        }
        
        $resultado = $this->propiedadServicioRepository->detach($propiedadId, $servicioId);
        
        if ($resultado) {
            $this->logService->registrar(
                'servicio_desasignado',
                "Usuario {$usuarioId} desasignó servicio {$servicioId} de propiedad {$propiedadId}",
                $usuarioId
            );
        }
        
        return $resultado;
    }
    
    /**
     * Asignar múltiples servicios a una propiedad
     */
    public function asignarMultiplesServicios(int $propiedadId, array $servicioIds, int $usuarioId): array
    {
        // Validar que la propiedad existe
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        
        // Validar que todos los servicios existan
        foreach ($servicioIds as $servicioId) {
            $servicio = $this->servicioRepository->findById($servicioId);
            if (!$servicio) {
                throw new \Exception("El servicio ID {$servicioId} no existe", 404);
            }
        }
        
        $resultados = $this->propiedadServicioRepository->attachMultiple($propiedadId, $servicioIds);
        
        // Registrar actividad
        if (!empty($resultados['asignados'])) {
            $this->logService->registrar(
                'servicios_multiples_asignados',
                "Usuario {$usuarioId} asignó servicios " . implode(',', $resultados['asignados']) . " a propiedad {$propiedadId}",
                $usuarioId
            );
        }
        
        return $resultados;
    }
    
    /**
     * Sincronizar servicios de una propiedad (reemplaza todos)
     */
    public function sincronizarServicios(int $propiedadId, array $servicioIds, int $usuarioId): array
    {
        // Validar que la propiedad existe
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        
        // Validar que todos los servicios existan
        foreach ($servicioIds as $servicioId) {
            $servicio = $this->servicioRepository->findById($servicioId);
            if (!$servicio) {
                throw new \Exception("El servicio ID {$servicioId} no existe", 404);
            }
        }
        
        $resultados = $this->propiedadServicioRepository->sync($propiedadId, $servicioIds);
        
        // Registrar actividad
        $this->logService->registrar(
            'servicios_sincronizados',
            "Usuario {$usuarioId} sincronizó servicios de propiedad {$propiedadId}",
            $usuarioId
        );
        
        return $resultados;
    }
    
    /**
     * Obtener IDs de servicios de una propiedad
     */
    public function obtenerIdsServiciosPorPropiedad(int $propiedadId): array
    {
        return $this->propiedadServicioRepository->getServicioIdsByPropiedad($propiedadId);
    }
}