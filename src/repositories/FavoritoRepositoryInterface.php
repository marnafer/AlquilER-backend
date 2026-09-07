<?php

namespace App\Repositories;

interface FavoritoRepositoryInterface
{
    /**
     * Obtener todos los favoritos de un usuario
     * 
     * @param int $usuarioId
     * @return array
     */
    public function getByUserId(int $usuarioId): array;
    
    /**
     * Verificar si una propiedad está en favoritos de un usuario
     * 
     * @param int $usuarioId
     * @param int $propiedadId
     * @return bool
     */
    public function exists(int $usuarioId, int $propiedadId): bool;
    
    /**
     * Agregar una propiedad a favoritos
     * 
     * @param int $usuarioId
     * @param int $propiedadId
     * @return bool
     */
    public function add(int $usuarioId, int $propiedadId): bool;
    
    /**
     * Eliminar una propiedad de favoritos
     * 
     * @param int $usuarioId
     * @param int $propiedadId
     * @return bool
     */
    public function remove(int $usuarioId, int $propiedadId): bool;
    
    /**
     * Obtener cantidad de favoritos de una propiedad
     * 
     * @param int $propiedadId
     * @return int
     */
    public function countByPropiedad(int $propiedadId): int;
    
    /**
     * Obtener solo los IDs de propiedades favoritas de un usuario
     * 
     * @param int $usuarioId
     * @return array
     */
    public function getPropiedadIdsByUser(int $usuarioId): array;
}