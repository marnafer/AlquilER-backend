<?php

namespace App\Repositories;

interface ConsultaRepositoryInterface
{
    /**
     * Obtener todas las consultas con filtros
     */
    public function getAll(array $filtros = []): array;
    
    /**
     * Buscar una consulta por ID
     */
    public function findById(int $id);
    
    /**
     * Crear una nueva consulta
     */
    public function create(array $data): int;
    
    /**
     * Actualizar una consulta
     */
    public function update(int $id, array $data): bool;
    
    /**
     * Eliminar una consulta (soft delete)
     */
    public function delete(int $id): bool;
    
    /**
     * Restaurar una consulta eliminada
     */
    public function restore(int $id): bool;
    
    /**
     * Obtener consultas por usuario
     */
    public function getByUsuario(int $usuarioId): array;
    
    /**
     * Obtener consultas por propiedad
     */
    public function getByPropiedad(int $propiedadId): array;
}