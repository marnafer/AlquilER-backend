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
    
    public function listarResenas(array $filtros = []): array
    {
        return $this->resenaRepository->getAll($filtros);
    }
    
    public function obtenerResena(int $id)
    {
        $resena = $this->resenaRepository->findById($id);
        if (!$resena) {
            throw new \Exception("Reseña no encontrada", 404);
        }
        return $resena;
    }
    
    /**
     * Crear una nueva reseña (bidireccional)
     */
    public function crearResena(array $data): int
    {
        // Validar que la reserva existe y está finalizada
        $reserva = $this->reservaRepository->findById($data['reserva_id']);
        if (!$reserva) {
            throw new \Exception("La reserva no existe", 404);
        }
        
        if ($reserva->estado !== 'finalizada') {
            throw new \Exception("Solo se pueden calificar reservas finalizadas", 400);
        }
        
        // Validar tipo
        $tiposValidos = ['propiedad', 'inquilino'];
        if (!in_array($data['tipo'], $tiposValidos)) {
            throw new \Exception("Tipo de reseña inválido. Debe ser 'propiedad' o 'inquilino'", 400);
        }
        
        // Validar calificación
        if ($data['calificacion'] < 1 || $data['calificacion'] > 5) {
            throw new \Exception("La calificación debe ser entre 1 y 5", 400);
        }
        
        // Obtener la propiedad de la reserva
        $propiedad = $this->propiedadRepository->findById($reserva->propiedad_id);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        
        // Determinar calificado y calificador según el tipo
        if ($data['tipo'] === 'propiedad') {
            // El inquilino califica la propiedad → calificado = propietario
            $data['calificado_id'] = $propiedad->usuario_id;
            $data['calificador_id'] = $reserva->usuario_id;
        } else {
            // El propietario califica al inquilino → calificado = inquilino
            $data['calificado_id'] = $reserva->usuario_id;
            $data['calificador_id'] = $propiedad->usuario_id;
        }
        
        // Validar que no sea auto-calificación
        if ($data['calificado_id'] == $data['calificador_id']) {
            throw new \Exception("No puedes calificarte a ti mismo", 400);
        }
        
        // Validar que no exista una reseña de este tipo para esta reserva
        if ($this->resenaRepository->existePorReservaYTipo($data['reserva_id'], $data['tipo'])) {
            throw new \Exception("Esta reserva ya tiene una reseña de tipo '{$data['tipo']}'", 409);
        }
        
        // Establecer fecha de publicación
        $data['fecha_publicacion'] = date('Y-m-d H:i:s');
        
        // Crear reseña
        $id = $this->resenaRepository->create($data);
        
        // Registrar actividad (Respetando la firma: int $usuarioId, string $accion)
        $this->logService->registrar(
            (int) $data['calificador_id'],
            'resena_creada'
        );
        
        return $id;
    }
    
    public function actualizarResena(int $id, array $data, int $usuarioId): bool
    {
        $resena = $this->resenaRepository->findById($id);
        if (!$resena) {
            throw new \Exception("Reseña no encontrada", 404);
        }
        
        // Verificar permisos (solo el calificador o admin)
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario || ($usuario->rol_id != 3 && $resena->calificador_id != $usuarioId)) {
            throw new \Exception("No autorizado", 403);
        }
        
        // Validar calificación si viene
        if (isset($data['calificacion']) && ($data['calificacion'] < 1 || $data['calificacion'] > 5)) {
            throw new \Exception("La calificación debe ser entre 1 y 5", 400);
        }
        
        // No permitir cambiar campos críticos
        unset($data['tipo']);
        unset($data['calificado_id']);
        unset($data['calificador_id']);
        unset($data['reserva_id']);
        unset($data['fecha_publicacion']);
        
        $resultado = $this->resenaRepository->update($id, $data);
        
        if ($resultado) {
            $this->logService->registrar(
                (int) $usuarioId,
                'resena_actualizada'
            );
        }
        
        return $resultado;
    }
    
    public function eliminarResena(int $id, int $usuarioId): bool
    {
        $resena = $this->resenaRepository->findById($id);
        if (!$resena) {
            throw new \Exception("Reseña no encontrada", 404);
        }
        
        // Solo admin puede eliminar
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario || $usuario->rol_id != 3) {
            throw new \Exception("No autorizado", 403);
        }
        
        $resultado = $this->resenaRepository->delete($id);
        
        if ($resultado) {
            $this->logService->registrar(
                (int) $usuarioId,
                'resena_eliminada'
            );
        }
        
        return $resultado;
    }
    
    public function restaurarResena(int $id, int $usuarioId): bool
    {
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario || $usuario->rol_id != 3) {
            throw new \Exception("No autorizado", 403);
        }
        
        $resultado = $this->resenaRepository->restore($id);
        
        if ($resultado) {
            $this->logService->registrar(
                (int) $usuarioId,
                'resena_restaurada'
            );
        } else {
            throw new \Exception("No se pudo restaurar la reseña o no existe", 404);
        }
        
        return $resultado;
    }
    
    public function obtenerResenasPorReserva(int $reservaId): array
    {
        $reserva = $this->reservaRepository->findById($reservaId);
        if (!$reserva) {
            throw new \Exception("La reserva no existe", 404);
        }
        return $this->resenaRepository->getByReserva($reservaId);
    }
    
    public function obtenerResenasPorPropiedad(int $propiedadId): array
    {
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        return $this->resenaRepository->getByPropiedad($propiedadId);
    }
    
    public function obtenerResenasPorUsuario(int $usuarioId): array
    {
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario) {
            throw new \Exception("El usuario no existe", 404);
        }
        return $this->resenaRepository->getByUsuario($usuarioId);
    }
    
    public function obtenerResenasPorCalificador(int $calificadorId): array
    {
        $usuario = $this->usuarioRepository->findById($calificadorId);
        if (!$usuario) {
            throw new \Exception("El usuario no existe", 404);
        }
        return $this->resenaRepository->getByCalificador($calificadorId);
    }
    
    public function obtenerPromedioPropiedad(int $propiedadId): float
    {
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        return $this->resenaRepository->getPromedioByPropiedad($propiedadId);
    }
    
    public function obtenerPromedioUsuario(int $usuarioId): float
    {
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario) {
            throw new \Exception("El usuario no existe", 404);
        }
        return $this->resenaRepository->getPromedioByUsuario($usuarioId);
    }
    
    public function existeResenaPorReservaYTipo(int $reservaId, string $tipo): bool
    {
        return $this->resenaRepository->existePorReservaYTipo($reservaId, $tipo);
    }
}