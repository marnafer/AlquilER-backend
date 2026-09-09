<?php

namespace App\Services;

use App\Repositories\ResenaRepositoryInterface;
use App\Repositories\ReservaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\LogActividadService;

class ResenaService
{
    private ResenaRepositoryInterface $resenaRepository;
    private ReservaRepositoryInterface $reservaRepository;
    private PropiedadRepositoryInterface $propiedadRepository;
    private UsuarioRepositoryInterface $usuarioRepository;
    private LogActividadService $logService;
    
    public function __construct(
        ResenaRepositoryInterface $resenaRepository,
        ReservaRepositoryInterface $reservaRepository,
        PropiedadRepositoryInterface $propiedadRepository,
        UsuarioRepositoryInterface $usuarioRepository,
        LogActividadService $logService
    ) {
        $this->resenaRepository = $resenaRepository;
        $this->reservaRepository = $reservaRepository;
        $this->propiedadRepository = $propiedadRepository;
        $this->usuarioRepository = $usuarioRepository;
        $this->logService = $logService;
    }
    
    /**
     * Obtener todas las reseñas con filtros
     */
    public function listarResenas(array $filtros = []): array
    {
        return $this->resenaRepository->getAll($filtros);
    }
    
    /**
     * Obtener una reseña por ID
     */
    public function obtenerResena(int $id)
    {
        $resena = $this->resenaRepository->findById($id);
        
        if (!$resena) {
            throw new \Exception("Reseña no encontrada", 404);
        }
        
        return $resena;
    }
    
    /**
     * Crear una nueva reseña
     */
    public function crearResena(array $data): int
    {
        // Validar que la reserva existe
        $reserva = $this->reservaRepository->findById($data['reserva_id']);
        if (!$reserva) {
            throw new \Exception("La reserva no existe", 404);
        }
        
        // Validar que la reserva esté finalizada
        if ($reserva->estado !== 'finalizada') {
            throw new \Exception("Solo se pueden calificar reservas finalizadas", 400);
        }
        
        // Validar que no exista una reseña para esta reserva
        if ($this->resenaRepository->existePorReserva($data['reserva_id'])) {
            throw new \Exception("Esta reserva ya tiene una reseña", 409);
        }
        
        // Validar calificación
        if ($data['calificacion'] < 1 || $data['calificacion'] > 5) {
            throw new \Exception("La calificación debe ser entre 1 y 5", 400);
        }
        
        // Establecer fecha de publicación
        $data['fecha_publicacion'] = date('Y-m-d H:i:s');
        
        // Crear reseña
        $id = $this->resenaRepository->create($data);
        
        // Registrar actividad
        $this->logService->registrar(
            'resena_creada',
            "Usuario {$reserva->usuario_id} creó reseña ID: {$id} para reserva {$data['reserva_id']}",
            $reserva->usuario_id
        );
        
        return $id;
    }
    
    /**
     * Actualizar una reseña existente
     */
    public function actualizarResena(int $id, array $data, int $usuarioId): bool
    {
        $resena = $this->resenaRepository->findById($id);
        if (!$resena) {
            throw new \Exception("Reseña no encontrada", 404);
        }
        
        // Verificar permisos (solo el dueño o admin puede actualizar)
        $usuario = $this->usuarioRepository->findById($usuarioId);
        $reserva = $this->reservaRepository->findById($resena->reserva_id);
        
        if (!$usuario || ($usuario->rol_id != 3 && $reserva->usuario_id != $usuarioId)) {
            throw new \Exception("No autorizado", 403);
        }
        
        // Validar calificación si viene
        if (isset($data['calificacion']) && ($data['calificacion'] < 1 || $data['calificacion'] > 5)) {
            throw new \Exception("La calificación debe ser entre 1 y 5", 400);
        }
        
        $resultado = $this->resenaRepository->update($id, $data);
        
        if ($resultado) {
            $this->logService->registrar(
                'resena_actualizada',
                "Usuario actualizó reseña ID: {$id}",
                $usuarioId
            );
        }
        
        return $resultado;
    }
    
    /**
     * Eliminar una reseña (soft delete)
     */
    public function eliminarResena(int $id, int $usuarioId): bool
    {
        $resena = $this->resenaRepository->findById($id);
        if (!$resena) {
            throw new \Exception("Reseña no encontrada", 404);
        }
        
        // Verificar permisos (solo admin puede eliminar)
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario || $usuario->rol_id != 3) {
            throw new \Exception("No autorizado", 403);
        }
        
        $resultado = $this->resenaRepository->delete($id);
        
        if ($resultado) {
            $this->logService->registrar(
                'resena_eliminada',
                "Usuario {$usuarioId} eliminó reseña ID: {$id}",
                $usuarioId
            );
        }
        
        return $resultado;
    }
    
    /**
     * Restaurar una reseña eliminada
     */
    public function restaurarResena(int $id, int $usuarioId): bool
    {
        // Verificar permisos (solo admin puede restaurar)
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario || $usuario->rol_id != 3) {
            throw new \Exception("No autorizado", 403);
        }
        
        $resultado = $this->resenaRepository->restore($id);
        
        if ($resultado) {
            $this->logService->registrar(
                'resena_restaurada',
                "Usuario {$usuarioId} restauró reseña ID: {$id}",
                $usuarioId
            );
        } else {
            throw new \Exception("No se pudo restaurar la reseña o no existe", 404);
        }
        
        return $resultado;
    }
    
    /**
     * Obtener reseñas por reserva
     */
    public function obtenerResenasPorReserva(int $reservaId): array
    {
        $reserva = $this->reservaRepository->findById($reservaId);
        if (!$reserva) {
            throw new \Exception("La reserva no existe", 404);
        }
        
        return $this->resenaRepository->getByReserva($reservaId);
    }
    
    /**
     * Obtener reseñas por propiedad
     */
    public function obtenerResenasPorPropiedad(int $propiedadId): array
    {
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        
        return $this->resenaRepository->getByPropiedad($propiedadId);
    }
    
    /**
     * Obtener promedio de calificación de una propiedad
     */
    public function obtenerPromedioPropiedad(int $propiedadId): float
    {
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        
        return $this->resenaRepository->getPromedioByPropiedad($propiedadId);
    }
    
    /**
     * Verificar si una reserva tiene reseña
     */
    public function existeResenaPorReserva(int $reservaId): bool
    {
        return $this->resenaRepository->existePorReserva($reservaId);
    }
}