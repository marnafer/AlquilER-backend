<?php

namespace App\Services;

use App\Repositories\ConsultaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Services\LogActividadService;

class ConsultaService
{
    private ConsultaRepositoryInterface $consultaRepository;
    private PropiedadRepositoryInterface $propiedadRepository;
    private UsuarioRepositoryInterface $usuarioRepository;
    private LogActividadService $logService;
    
    public function __construct(
        ConsultaRepositoryInterface $consultaRepository,
        PropiedadRepositoryInterface $propiedadRepository,
        UsuarioRepositoryInterface $usuarioRepository,
        LogActividadService $logService
    ) {
        $this->consultaRepository = $consultaRepository;
        $this->propiedadRepository = $propiedadRepository;
        $this->usuarioRepository = $usuarioRepository;
        $this->logService = $logService;
    }
    
    /**
     * Obtener todas las consultas con filtros
     */
    public function listarConsultas(array $filtros = []): array
    {
        return $this->consultaRepository->getAll($filtros);
    }
    
    /**
     * Obtener una consulta por ID
     */
    public function obtenerConsulta(int $id)
    {
        $consulta = $this->consultaRepository->findById($id);
        
        if (!$consulta) {
            throw new \Exception("Consulta no encontrada", 404);
        }
        
        return $consulta;
    }
    
    /**
     * Crear una nueva consulta
     */
    public function crearConsulta(array $data): int
    {
        // Validar que la propiedad existe
        $propiedad = $this->propiedadRepository->findById($data['propiedad_id']);
        if (!$propiedad) {
            throw new \Exception("La propiedad no existe", 404);
        }
        
        // Establecer fecha de consulta
        $data['fecha_consulta'] = date('Y-m-d H:i:s');
        
        // Crear consulta
        $id = $this->consultaRepository->create($data);
        
        // Registrar actividad (Respetando la firma: int $usuarioId, string $accion, ?string $ipAddress)
        $this->logService->registrar(
            (int) $data['usuario_id'],
            'consulta_creada'
        );
        
        return $id;
    }
    
    /**
     * Actualizar una consulta existente
     */
    public function actualizarConsulta(int $id, array $data, int $usuarioId): bool
    {
        $consulta = $this->consultaRepository->findById($id);
        if (!$consulta) {
            throw new \Exception("Consulta no encontrada", 404);
        }
        
        // Verificar permisos (solo el dueño o admin puede actualizar)
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario || ($usuario->rol_id != 3 && $consulta->usuario_id != $usuarioId)) {
            throw new \Exception("No autorizado", 403);
        }
        
        $resultado = $this->consultaRepository->update($id, $data);
        
        if ($resultado) {
            $this->logService->registrar(
                (int) $usuarioId,
                'consulta_actualizada'
            );
        }
        
        return $resultado;
    }
    
    /**
     * Eliminar una consulta (soft delete)
     */
    public function eliminarConsulta(int $id, int $usuarioId): bool
    {
        $consulta = $this->consultaRepository->findById($id);
        if (!$consulta) {
            throw new \Exception("Consulta no encontrada", 404);
        }
        
        // Verificar permisos (solo admin puede eliminar)
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario || $usuario->rol_id != 3) {
            throw new \Exception("No autorizado", 403);
        }
        
        $resultado = $this->consultaRepository->delete($id);
        
        if ($resultado) {
            $this->logService->registrar(
                (int) $usuarioId,
                'consulta_eliminada'
            );
        }
        
        return $resultado;
    }
    
    /**
     * Restaurar una consulta eliminada
     */
    public function restaurarConsulta(int $id, int $usuarioId): bool
    {
        // Verificar permisos (solo admin puede restaurar)
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario || $usuario->rol_id != 3) {
            throw new \Exception("No autorizado", 403);
        }
        
        $resultado = $this->consultaRepository->restore($id);
        
        if ($resultado) {
            $this->logService->registrar(
                (int) $usuarioId,
                'consulta_restaurada'
            );
        } else {
            throw new \Exception("No se pudo restaurar la consulta o no existe", 404);
        }
        
        return $resultado;
    }
    
    /**
     * Obtener consultas de un usuario
     */
    public function obtenerConsultasPorUsuario(int $usuarioId, int $usuarioActualId, int $rolId): array
    {
        // Verificar permisos (solo el propio usuario o admin)
        if ($rolId != 3 && $usuarioActualId != $usuarioId) {
            throw new \Exception("No autorizado", 403);
        }
        
        return $this->consultaRepository->getByUsuario($usuarioId);
    }
    
    /**
     * Obtener consultas de una propiedad (con validación de permisos)
     */
    public function obtenerConsultasPorPropiedad(int $propiedadId, int $usuarioId, int $rolId): array
    {
        // Verificar que la propiedad existe
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        if (!$propiedad) {
            throw new \Exception("Propiedad no encontrada", 404);
        }
        
        // Verificar permisos (solo el dueño o admin)
        if ($rolId != 3 && $propiedad->usuario_id != $usuarioId) {
            throw new \Exception("No autorizado", 403);
        }
        
        return $this->consultaRepository->getByPropiedad($propiedadId);
    }
}