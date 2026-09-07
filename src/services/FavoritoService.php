<?php

namespace App\Services;

use App\Repositories\FavoritoRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Services\LogActividadService;

class FavoritoService
{
    private FavoritoRepositoryInterface $favoritoRepository;
    private PropiedadRepositoryInterface $propiedadRepository;
    private LogActividadService $logService;
    
    public function __construct(
        FavoritoRepositoryInterface $favoritoRepository,
        PropiedadRepositoryInterface $propiedadRepository,
        LogActividadService $logService
    ) {
        $this->favoritoRepository = $favoritoRepository;
        $this->propiedadRepository = $propiedadRepository;
        $this->logService = $logService;
    }
    
    /**
     * Obtener todos los favoritos de un usuario
     */
    public function obtenerFavoritos(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }
        
        return $this->favoritoRepository->getByUserId($usuarioId);
    }
    
    /**
     * Obtener solo los IDs de propiedades favoritas
     */
    public function obtenerIdsFavoritos(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }
        
        return $this->favoritoRepository->getPropiedadIdsByUser($usuarioId);
    }
    
    /**
     * Verificar si una propiedad está en favoritos
     */
    public function esFavorito(int $usuarioId, int $propiedadId): bool
    {
        if ($usuarioId <= 0 || $propiedadId <= 0) {
            return false;
        }
        
        return $this->favoritoRepository->exists($usuarioId, $propiedadId);
    }
    
    /**
     * Agregar una propiedad a favoritos
     */
    public function agregarFavorito(int $usuarioId, int $propiedadId): bool
    {
        if ($usuarioId <= 0) {
            throw new \InvalidArgumentException("ID de usuario inválido", 400);
        }
        
        if ($propiedadId <= 0) {
            throw new \InvalidArgumentException("ID de propiedad inválido", 400);
        }
        
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        
        if (isset($propiedad->usuario_id) && $propiedad->usuario_id == $usuarioId) {
            throw new \Exception("No puedes agregar tu propia propiedad a favoritos", 400);
        }
        
        $resultado = $this->favoritoRepository->add($usuarioId, $propiedadId);
        
        if ($resultado) {
            $this->logService->registrar(
                'favorito_agregado',
                "Usuario {$usuarioId} agregó propiedad {$propiedadId} a favoritos",
                $usuarioId
            );
        }
        
        return $resultado;
    }
    
    /**
     * Eliminar una propiedad de favoritos
     */
    public function eliminarFavorito(int $usuarioId, int $propiedadId): bool
    {
        if ($usuarioId <= 0) {
            throw new \InvalidArgumentException("ID de usuario inválido", 400);
        }
        
        if ($propiedadId <= 0) {
            throw new \InvalidArgumentException("ID de propiedad inválido", 400);
        }
        
        if (!$this->favoritoRepository->exists($usuarioId, $propiedadId)) {
            throw new \Exception("La propiedad no está en favoritos", 404);
        }
        
        $resultado = $this->favoritoRepository->remove($usuarioId, $propiedadId);
        
        if ($resultado) {
            $this->logService->registrar(
                'favorito_eliminado',
                "Usuario {$usuarioId} eliminó propiedad {$propiedadId} de favoritos",
                $usuarioId
            );
        }
        
        return $resultado;
    }
    
    /**
     * Obtener cantidad de favoritos de una propiedad
     */
    public function contarFavoritos(int $propiedadId): int
    {
        if ($propiedadId <= 0) {
            return 0;
        }
        
        return $this->favoritoRepository->countByPropiedad($propiedadId);
    }
    
    /**
     * Marcar propiedades como favoritas en un listado
     */
    public function marcarFavoritosEnListado(int $usuarioId, array $propiedades): array
    {
        if (empty($propiedades) || $usuarioId <= 0) {
            return $propiedades;
        }
        
        $idsFavoritos = $this->favoritoRepository->getPropiedadIdsByUser($usuarioId);
        
        foreach ($propiedades as &$propiedad) {
            $propiedad['es_favorito'] = in_array($propiedad['id'] ?? null, $idsFavoritos);
        }
        
        return $propiedades;
    }
}