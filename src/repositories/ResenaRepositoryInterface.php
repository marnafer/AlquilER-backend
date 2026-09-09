<?php

namespace App\Repositories;

interface ResenaRepositoryInterface
{
    /**
     * Obtener todas las reseñas con filtros
     */
    public function getAll(array $filtros = []): array;
    
    /**
     * Buscar una reseña por ID
     */
    public function findById(int $id);
    
    /**
     * Crear una nueva reseña
     */
    public function create(array $data): int;
    
    /**
     * Actualizar una reseña
     */
    public function update(int $id, array $data): bool;
    
    /**
     * Eliminar una reseña (soft delete)
     */
    public function delete(int $id): bool;
    
    /**
     * Restaurar una reseña eliminada
     */
    public function restore(int $id): bool;
    
    /**
     * Obtener reseñas por reserva
     */
    public function getByReserva(int $reservaId): array;
    
    /**
     * Obtener promedio de calificación por propiedad
     */
    public function getPromedioByPropiedad(int $propiedadId): float;
    
    /**
     * Obtener reseñas por propiedad (a través de reservas)
     */
    public function getByPropiedad(int $propiedadId): array;
    
    /**
     * Verificar si una reserva ya tiene reseña
     */
    public function existePorReserva(int $reservaId): bool;
}