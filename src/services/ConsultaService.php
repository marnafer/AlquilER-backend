<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\Consulta;
use App\Policies\ConsultaPolicy;
use App\Repositories\ConsultaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Repositories\MensajeConsultaRepositoryInterface;
use App\Sanitizers\ConsultaSanitizer;
use App\Validators\ConsultaValidator;
use App\Services\LogActividadService;
use Illuminate\Database\Capsule\Manager as DB;

class ConsultaService
{
    public function __construct(
        private readonly ConsultaRepositoryInterface $consultaRepository,
        private readonly PropiedadRepositoryInterface $propiedadRepository,
        private readonly UsuarioRepositoryInterface $usuarioRepository,
        private readonly MensajeConsultaRepositoryInterface $mensajeConsultaRepository,
        private readonly LogActividadService $logService,
        private readonly ConsultaPolicy $policy
    ) {
    }
    
    public function listarConsultas(int $rolId, array $rawFiltros = []): array
    {
        if (!$this->policy->puedeAdministrar($rolId)) {
            throw new UnauthorizedException('No autorizado para ver el listado global de consultas');
        }

        $filtros = ConsultaSanitizer::sanitizarConsulta($rawFiltros);
        
        // Eliminamos los valores nulos o vacíos para enviar solo los filtros activos
        $filtrosLimpios = array_filter($filtros, fn($value) => !is_null($value) && $value !== '');
        
        return $this->consultaRepository->getAll($filtrosLimpios);
    }
    
    public function obtenerConsulta($rawId): Consulta
    {
        $id = ConsultaSanitizer::sanitizarId($rawId);
        
        $validacion = ConsultaValidator::validarSoloIdConsulta($id);
        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $consulta = $this->consultaRepository->findById($id);
        
        if (!$consulta) {
            throw new ValidationException(['id' => ['Consulta no encontrada']]);
        }
        
        return $consulta;
    }

    public function obtenerConsultaAutorizada($rawConsultaId, int $usuarioLogueadoId): Consulta
    {
        $consultaId = ConsultaSanitizer::sanitizarId($rawConsultaId);
        $validacion = ConsultaValidator::validarSoloIdConsulta($consultaId);
        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $consulta = $this->obtenerConsulta($consultaId);

        if (!$this->policy->puedeParticipar($usuarioLogueadoId, $consulta)) {
            throw new UnauthorizedException('No tienes permiso para acceder a esta consulta');
        }

        return $consulta;
    }
    
    public function crearConsulta(array $rawData): int
    {
        $data = ConsultaSanitizer::sanitizarConsulta($rawData);

        $validacion = ConsultaValidator::validarCrearConsulta($data);
        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $propiedadId = (int) $data['propiedad_id'];
        $propiedad = $this->propiedadRepository->findById($propiedadId);
        
        if (!$propiedad) {
            throw new ValidationException(['propiedad_id' => ['La propiedad no existe']]);
        }

        return DB::transaction(function () use ($data, $propiedadId) {
            $consultaId = $this->consultaRepository->create([
                'propiedad_id' => $propiedadId,
                'usuario_id' => $data['usuario_id'],
                'fecha_consulta' => date('Y-m-d H:i:s'),
            ]);

            if (!empty($data['mensaje'])) {
                $this->mensajeConsultaRepository->create([
                    'consulta_id' => $consultaId,
                    'usuario_id' => $data['usuario_id'],
                    'mensaje' => $data['mensaje'],
                    'fecha_mensaje' => date('Y-m-d H:i:s')
                ]);
            }

            $this->logService->registrar((int) $data['usuario_id'], 'consulta_creada');

            return $consultaId;
        });
    }
    
    public function actualizarConsulta($rawId, array $rawData, int $usuarioId): bool
    {
        $id = ConsultaSanitizer::sanitizarId($rawId);
        $validacionId = ConsultaValidator::validarSoloIdConsulta($id);
        if (!$validacionId['success']) {
            throw new ValidationException($validacionId['errors']);
        }

        $consulta = $this->obtenerConsulta($id);
        $usuario = $this->usuarioRepository->findById($usuarioId);
        
        if (!$usuario || !$this->policy->puedeActualizar($usuarioId, $usuario->rol_id, $consulta)) {
            throw new UnauthorizedException('No tienes permiso para actualizar esta consulta');
        }

        $data = ConsultaSanitizer::sanitizarConsulta(array_merge($rawData, ['id' => $id]));
        
        $validacion = ConsultaValidator::validarActualizarConsulta($data);
        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }
        
        $resultado = $this->consultaRepository->update($id, $data);
        
        if ($resultado) {
            $this->logService->registrar((int) $usuarioId, 'consulta_actualizada');
        }
        
        return $resultado;
    }
    
    public function eliminarConsulta($rawId, int $usuarioId): bool
    {
        $id = ConsultaSanitizer::sanitizarId($rawId);
        $validacionId = ConsultaValidator::validarSoloIdConsulta($id);
        if (!$validacionId['success']) {
            throw new ValidationException($validacionId['errors']);
        }

        $consulta = $this->obtenerConsulta($id);
        $usuario = $this->usuarioRepository->findById($usuarioId);
        
        if (!$usuario || !$this->policy->puedeAdministrar($usuario->rol_id)) {
            throw new UnauthorizedException('No autorizado para eliminar consultas');
        }
        
        $resultado = $this->consultaRepository->delete($id);
        
        if ($resultado) {
            $this->logService->registrar((int) $usuarioId, 'consulta_eliminada');
        }
        
        return $resultado;
    }
    
    public function restaurarConsulta($rawId, int $usuarioId): bool
    {
        $id = ConsultaSanitizer::sanitizarId($rawId);
        $validacionId = ConsultaValidator::validarSoloIdConsulta($id);
        if (!$validacionId['success']) {
            throw new ValidationException($validacionId['errors']);
        }

        $usuario = $this->usuarioRepository->findById($usuarioId);
        
        if (!$usuario || !$this->policy->puedeAdministrar($usuario->rol_id)) {
            throw new UnauthorizedException('No autorizado para restaurar consultas');
        }
        
        $resultado = $this->consultaRepository->restore($id);
        
        if ($resultado) {
            $this->logService->registrar((int) $usuarioId, 'consulta_restaurada');
        } else {
            throw new ValidationException(['id' => ['No se pudo restaurar la consulta o no existe']]);
        }
        
        return $resultado;
    }
    
    public function obtenerConsultasPorUsuario($rawUsuarioConsultadoId, int $usuarioLogueadoId, int $rolId): array
    {
        $usuarioConsultadoId = ConsultaSanitizer::sanitizarId($rawUsuarioConsultadoId);
        
        if (!$usuarioConsultadoId || $usuarioConsultadoId <= 0) {
            throw new ValidationException(['usuario_id' => ['ID de usuario inválido']]);
        }

        if (!$this->policy->puedeVerDeUsuario($usuarioLogueadoId, $rolId, $usuarioConsultadoId)) {
            throw new UnauthorizedException('No tienes permiso para ver las consultas de este usuario');
        }
        
        return $this->consultaRepository->getByUsuario($usuarioConsultadoId);
    }
    
    public function obtenerConsultasPorPropiedad($rawPropiedadId, int $usuarioId, int $rolId): array
    {
        $propiedadId = ConsultaSanitizer::sanitizarId($rawPropiedadId);
        $validacion = ConsultaValidator::validarPropiedadId($propiedadId);
        
        if (!$validacion['success']) {
            throw new ValidationException(['propiedad_id' => [$validacion['error']]]);
        }

        $propiedad = $this->propiedadRepository->findById($propiedadId);
        
        if (!$propiedad) {
            throw new ValidationException(['propiedad_id' => ['Propiedad no encontrada']]);
        }
        
        if (!$this->policy->puedeVerDePropiedad($usuarioId, $rolId, $propiedad)) {
            throw new UnauthorizedException('No tienes permiso para ver las consultas de esta propiedad');
        }
        
        return $this->consultaRepository->getByPropiedad($propiedadId);
    }
}