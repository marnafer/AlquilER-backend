<?php

namespace App\Repositories;

interface PropiedadServicioRepositoryInterface
{
    /**
     * Obtener todos los servicios de una propiedad
     */
    public function getByPropiedad(int $propiedadId): array;
    
    /**
     * Obtener todas las propiedades de un servicio
     */
    public function getByServicio(int $servicioId): array;
    
    /**
     * Verificar si una propiedad tiene un servicio
     */
    public function exists(int $propiedadId, int $servicioId): bool;
    
    /**
     * Asignar un servicio a una propiedad
     */
    public function attach(int $propiedadId, int $servicioId): bool;
    
    /**
     * Desasignar un servicio de una propiedad
     */
    public function detach(int $propiedadId, int $servicioId): bool;
    
    /**
     * Asignar múltiples servicios a una propiedad
     */
    public function attachMultiple(int $propiedadId, array $servicioIds): array;
    
    /**
     * Sincronizar servicios de una propiedad (reemplaza todos)
     */
    public function sync(int $propiedadId, array $servicioIds): array;
    
    /**
     * Obtener IDs de servicios de una propiedad
     */
    public function getServicioIdsByPropiedad(int $propiedadId): array;
}