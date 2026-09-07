<?php

namespace App\Services;

use App\Repositories\ReservaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Services\LogActividadService;

class ReservaService
{
    private ReservaRepositoryInterface $reservaRepository;
    private PropiedadRepositoryInterface $propiedadRepository;
    private LogActividadService $logService;
    
    public function __construct(
        ReservaRepositoryInterface $reservaRepository,
        PropiedadRepositoryInterface $propiedadRepository,
        LogActividadService $logService
    ) {
        $this->reservaRepository = $reservaRepository;
        $this->propiedadRepository = $propiedadRepository;
        $this->logService = $logService;
    }
    
    /**
     * Obtener todas las reservas con filtros
     */
    public function listarReservas(array $filtros = []): array
    {
        return $this->reservaRepository->getAll($filtros);
    }
    
    /**
     * Obtener una reserva por ID
     */
    public function obtenerReserva(int $id)
    {
        $reserva = $this->reservaRepository->findById($id);
        
        if (!$reserva) {
            throw new \Exception("Reserva no encontrada", 404);
        }
        
        return $reserva;
    }
    
    /**
     * Crear una nueva reserva
     */
    public function crearReserva(array $data): int
    {
        // Validar que la propiedad existe
        $propiedad = $this->propiedadRepository->findById($data['propiedad_id']);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        
        // Validar que la propiedad esté disponible
        if (!$propiedad->disponible) {
            throw new \Exception("La propiedad no está disponible para alquiler", 400);
        }
        
        // Validar fechas
        if (empty($data['fecha_inicio_alquiler']) || empty($data['fecha_fin_alquiler'])) {
            throw new \Exception("Las fechas de inicio y fin son obligatorias", 400);
        }
        
        $fechaInicio = $data['fecha_inicio_alquiler'];
        $fechaFin = $data['fecha_fin_alquiler'];
        
        if ($fechaInicio > $fechaFin) {
            throw new \Exception("La fecha de inicio no puede ser mayor a la fecha de fin", 400);
        }
        
        if ($fechaInicio < date('Y-m-d')) {
            throw new \Exception("La fecha de inicio no puede ser en el pasado", 400);
        }
        
        // Verificar disponibilidad
        if (!$this->reservaRepository->isAvailable($data['propiedad_id'], $fechaInicio, $fechaFin)) {
            throw new \Exception("La propiedad no está disponible en ese rango de fechas", 409);
        }
        
        // Establecer estado inicial
        $data['estado'] = 'pendiente';
        $data['fecha_reserva'] = date('Y-m-d H:i:s');
        
        // Crear reserva
        $id = $this->reservaRepository->create($data);
        
        // Registrar actividad
        $this->logService->registrar(
            'reserva_creada',
            "Usuario {$data['usuario_id']} creó reserva ID: {$id} para propiedad {$data['propiedad_id']}",
            $data['usuario_id']
        );
        
        return $id;
    }
    
    /**
     * Actualizar una reserva
     */
    public function actualizarReserva(int $id, array $data): bool
    {
        $reserva = $this->reservaRepository->findById($id);
        if (!$reserva) {
            throw new \Exception("Reserva no encontrada", 404);
        }
        
        // No permitir modificar reservas confirmadas o finalizadas
        if (in_array($reserva->estado, ['confirmada', 'finalizada'])) {
            throw new \Exception("No se puede modificar una reserva confirmada o finalizada", 400);
        }
        
        // Si se cambian las fechas, verificar disponibilidad
        if (isset($data['fecha_inicio_alquiler']) || isset($data['fecha_fin_alquiler'])) {
            $fechaInicio = $data['fecha_inicio_alquiler'] ?? $reserva->fecha_inicio_alquiler;
            $fechaFin = $data['fecha_fin_alquiler'] ?? $reserva->fecha_fin_alquiler;
            
            if ($fechaInicio > $fechaFin) {
                throw new \Exception("La fecha de inicio no puede ser mayor a la fecha de fin", 400);
            }
            
            // Verificar disponibilidad (excluyendo la reserva actual)
            if (!$this->reservaRepository->isAvailable($reserva->propiedad_id, $fechaInicio, $fechaFin)) {
                throw new \Exception("La propiedad no está disponible en ese rango de fechas", 409);
            }
        }
        
        $resultado = $this->reservaRepository->update($id, $data);
        
        if ($resultado) {
            $this->logService->registrar(
                'reserva_actualizada',
                "Usuario actualizó reserva ID: {$id}",
                $data['usuario_id'] ?? $reserva->usuario_id
            );
        }
        
        return $resultado;
    }
    
    /**
     * Eliminar una reserva (soft delete)
     */
    public function eliminarReserva(int $id, int $usuarioId): bool
    {
        $reserva = $this->reservaRepository->findById($id);
        if (!$reserva) {
            throw new \Exception("Reserva no encontrada", 404);
        }
        
        // No permitir eliminar reservas confirmadas o finalizadas
        if (in_array($reserva->estado, ['confirmada', 'finalizada'])) {
            throw new \Exception("No se puede eliminar una reserva confirmada o finalizada", 400);
        }
        
        $resultado = $this->reservaRepository->delete($id);
        
        if ($resultado) {
            $this->logService->registrar(
                'reserva_eliminada',
                "Usuario {$usuarioId} eliminó reserva ID: {$id}",
                $usuarioId
            );
        }
        
        return $resultado;
    }
    
    /**
     * Restaurar una reserva eliminada
     */
    public function restaurarReserva(int $id, int $usuarioId): bool
    {
        $resultado = $this->reservaRepository->restore($id);
        
        if ($resultado) {
            $this->logService->registrar(
                'reserva_restaurada',
                "Usuario {$usuarioId} restauró reserva ID: {$id}",
                $usuarioId
            );
        }
        
        return $resultado;
    }
    
    /**
     * Obtener reservas de un usuario
     */
    public function obtenerReservasPorUsuario(int $usuarioId): array
    {
        return $this->reservaRepository->getByUsuario($usuarioId);
    }
    
    /**
     * Obtener reservas de una propiedad
     */
    public function obtenerReservasPorPropiedad(int $propiedadId): array
    {
        return $this->reservaRepository->getByPropiedad($propiedadId);
    }
    
    /**
     * Cambiar estado de una reserva
     */
    public function cambiarEstadoReserva(int $id, string $estado, int $usuarioId): bool
    {
        $reserva = $this->reservaRepository->findById($id);
        if (!$reserva) {
            throw new \Exception("Reserva no encontrada", 404);
        }
        
        $estadosValidos = ['pendiente', 'confirmada', 'rechazada', 'cancelada', 'finalizada'];
        if (!in_array($estado, $estadosValidos)) {
            throw new \Exception("Estado inválido", 400);
        }
        
        // Validar transiciones de estado
        $transicionesValidas = [
            'pendiente' => ['confirmada', 'rechazada', 'cancelada'],
            'confirmada' => ['finalizada', 'cancelada'],
            'rechazada' => [],
            'cancelada' => [],
            'finalizada' => []
        ];
        
        if (!in_array($estado, $transicionesValidas[$reserva->estado])) {
            throw new \Exception("No se puede cambiar de '{$reserva->estado}' a '{$estado}'", 400);
        }
        
        $resultado = $this->reservaRepository->cambiarEstado($id, $estado);
        
        if ($resultado) {
            $this->logService->registrar(
                'reserva_cambio_estado',
                "Usuario {$usuarioId} cambió reserva {$id} a estado '{$estado}'",
                $usuarioId
            );
        }
        
        return $resultado;
    }
    
    /**
     * Verificar disponibilidad de una propiedad
     */
    public function verificarDisponibilidad(int $propiedadId, string $fechaInicio, string $fechaFin): bool
    {
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        
        if (!$propiedad->disponible) {
            return false;
        }
        
        return $this->reservaRepository->isAvailable($propiedadId, $fechaInicio, $fechaFin);
    }
}