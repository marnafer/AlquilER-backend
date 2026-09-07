<?php

namespace App\Repositories;

interface ReservaRepositoryInterface
{
    /**
     * Obtener todas las reservas con filtros
     * 
     * @param array $filtros
     * @return array
     */
    public function getAll(array $filtros = []): array;
    
    /**
     * Buscar una reserva por ID
     * 
     * @param int $id
     * @return object|null
     */
    public function findById(int $id);
    
    /**
     * Crear una nueva reserva
     * 
     * @param array $data
     * @return int
     */
    public function create(array $data): int;
    
    /**
     * Actualizar una reserva
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;
    
    /**
     * Eliminar una reserva (soft delete)
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
    
    /**
     * Restaurar una reserva eliminada
     * 
     * @param int $id
     * @return bool
     */
    public function restore(int $id): bool;
    
    /**
     * Obtener reservas por usuario
     * 
     * @param int $usuarioId
     * @return array
     */
    public function getByUsuario(int $usuarioId): array;
    
    /**
     * Obtener reservas por propiedad
     * 
     * @param int $propiedadId
     * @return array
     */
    public function getByPropiedad(int $propiedadId): array;
    
    /**
     * Verificar disponibilidad de una propiedad en un rango de fechas
     * 
     * @param int $propiedadId
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return bool
     */
    public function isAvailable(int $propiedadId, string $fechaInicio, string $fechaFin): bool;
    
    /**
     * Cambiar estado de una reserva
     * 
     * @param int $id
     * @param string $estado
     * @return bool
     */
    public function cambiarEstado(int $id, string $estado): bool;
}