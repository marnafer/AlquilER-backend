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
     * Obtener reseñas de propiedad (tipo 'propiedad')
     */
    public function getByPropiedad(int $propiedadId): array;
    
    /**
     * Obtener reseñas de un usuario (tipo 'inquilino')
     */
    public function getByUsuario(int $usuarioId): array;
    
    /**
     * Obtener reseñas donde un usuario es calificador
     */
    public function getByCalificador(int $calificadorId): array;
    
    /**
     * Obtener promedio de calificación de una propiedad
     */
    public function getPromedioByPropiedad(int $propiedadId): float;
    
    /**
     * Obtener promedio de calificación de un usuario
     */
    public function getPromedioByUsuario(int $usuarioId): float;
    
    /**
     * Verificar si una reserva ya tiene reseña de un tipo específico
     */
    public function existePorReservaYTipo(int $reservaId, string $tipo): bool;
    
    /**
     * Verificar si existe reseña para una reserva
     */
    public function existePorReserva(int $reservaId): bool;
}