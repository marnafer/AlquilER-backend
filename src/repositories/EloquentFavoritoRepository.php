<?php

namespace App\Repositories;

use App\Models\Favorito;

class EloquentFavoritoRepository implements FavoritoRepositoryInterface
{
    /**
     * Obtener todos los favoritos de un usuario con relaciones
     */
    public function getByUserId(int $usuarioId): array
    {
        return Favorito::where('usuario_id', $usuarioId)
            ->with([
                'propiedad',
                'propiedad.categoria',
                'propiedad.provincia',
                'propiedad.localidad',
                'propiedad.imagenes'
            ])
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();
    }
    
    /**
     * Verificar si existe un favorito
     */
    public function exists(int $usuarioId, int $propiedadId): bool
    {
        return Favorito::where('usuario_id', $usuarioId)
            ->where('propiedad_id', $propiedadId)
            ->exists();
    }
    
    /**
     * Agregar un favorito
     */
    public function add(int $usuarioId, int $propiedadId): bool
    {
        if ($this->exists($usuarioId, $propiedadId)) {
            return false;
        }
        
        return Favorito::create([
            'usuario_id' => $usuarioId,
            'propiedad_id' => $propiedadId
        ]) ? true : false;
    }
    
    /**
     * Eliminar un favorito
     */
    public function remove(int $usuarioId, int $propiedadId): bool
    {
        return Favorito::where('usuario_id', $usuarioId)
            ->where('propiedad_id', $propiedadId)
            ->delete() > 0;
    }
    
    /**
     * Contar favoritos de una propiedad
     */
    public function countByPropiedad(int $propiedadId): int
    {
        return Favorito::where('propiedad_id', $propiedadId)->count();
    }
    
    /**
     * Obtener solo IDs de propiedades favoritas
     */
    public function getPropiedadIdsByUser(int $usuarioId): array
    {
        return Favorito::where('usuario_id', $usuarioId)
            ->pluck('propiedad_id')
            ->toArray();
    }
}