<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\Consulta;
use App\Policies\ConsultaPolicy;
use App\Repositories\ConsultaRepositoryInterface;
use App\Repositories\MensajeConsultaRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\UsuarioRepositoryInterface;
use App\Sanitizers\ConsultaSanitizer;
use App\Validators\ConsultaValidator;
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

    public function listar(
        int $rolId,
        array $rawFiltros = []
    ): array {
        if (!$this->policy->puedeAdministrar($rolId)) {
            throw new ForbiddenException(
                'No autorizado para ver el listado global de consultas'
            );
        }

        $filtros = ConsultaSanitizer::sanitizarConsulta(
            $rawFiltros
        );

        $filtrosLimpios = array_filter(
            $filtros,
            fn($value) => !is_null($value) && $value !== ''
        );

        return $this->consultaRepository->getAll(
            $filtrosLimpios
        );
    }

    public function obtener($rawId): Consulta
    {
        $id = ConsultaSanitizer::sanitizarId($rawId);

        $validacion = ConsultaValidator::validarSoloIdConsulta($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $consulta = $this->consultaRepository->findById($id);

        if (!$consulta) {
            throw new NotFoundException(
                'Consulta no encontrada'
            );
        }

        return $consulta;
    }

    public function obtenerAutorizada(
        $rawConsultaId,
        int $usuarioLogueadoId
    ): Consulta {
        $consultaId = ConsultaSanitizer::sanitizarId(
            $rawConsultaId
        );

        $validacion = ConsultaValidator::validarSoloIdConsulta(
            $consultaId
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $consulta = $this->obtener($consultaId);

        if (
            !$this->policy->puedeParticipar(
                $usuarioLogueadoId,
                $consulta
            )
        ) {
            throw new ForbiddenException(
                'No tienes permiso para acceder a esta consulta'
            );
        }

        return $consulta;
    }

    /**
     * Alias de obtenerAutorizada.
     *
     * MensajeConsultaService lo invoca con este nombre.
     */
    public function obtenerConsultaAutorizada(
        $rawConsultaId,
        int $usuarioLogueadoId
    ): Consulta {
        return $this->obtenerAutorizada(
            $rawConsultaId,
            $usuarioLogueadoId
        );
    }

    public function crear(array $rawData): int
    {
        $data = ConsultaSanitizer::sanitizarConsulta(
            $rawData
        );

        $validacion = ConsultaValidator::validarCrearConsulta(
            $data
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $propiedadId = (int) $data['propiedad_id'];

        $propiedad = $this->propiedadRepository->findById(
            $propiedadId
        );

        if (!$propiedad) {
            throw new NotFoundException(
                'La propiedad no existe'
            );
        }

        return DB::transaction(
            function () use ($data, $propiedadId): int {
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

                $this->logService->registrar(
                    (int) $data['usuario_id'],
                    'consulta_creada'
                );

                return $consultaId;
            }
        );
    }

    public function actualizar(
        $rawId,
        array $rawData,
        int $usuarioId
    ): bool {
        $id = ConsultaSanitizer::sanitizarId($rawId);

        $validacionId = ConsultaValidator::validarSoloIdConsulta(
            $id
        );

        if (!$validacionId['success']) {
            throw new ValidationException(
                $validacionId['errors']
            );
        }

        $consulta = $this->obtener($id);

        if ($consulta->trashed()) {
            throw new NotFoundException(
                'Consulta no encontrada'
            );
        }

        $usuario = $this->usuarioRepository->findById(
            $usuarioId
        );

        if (
            !$usuario
            || !$this->policy->puedeActualizar(
                $usuarioId,
                $usuario->rol_id,
                $consulta
            )
        ) {
            throw new ForbiddenException(
                'No tienes permiso para actualizar esta consulta'
            );
        }

        $data = ConsultaSanitizer::sanitizarConsulta(
            array_merge(
                $rawData,
                ['id' => $id]
            )
        );

        $validacion = ConsultaValidator::validarActualizarConsulta(
            $data
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $mensaje = $this->mensajeConsultaRepository
            ->findByConsultaId($id)
            ->first();

        if ($mensaje) {
            $this->mensajeConsultaRepository->update(
                $mensaje->id,
                ['mensaje' => $data['mensaje']]
            );
        } else {
            $this->mensajeConsultaRepository->create([
                'consulta_id' => $id,
                'usuario_id' => $usuarioId,
                'mensaje' => $data['mensaje'],
                'fecha_mensaje' => date('Y-m-d H:i:s')
            ]);
        }

        $this->logService->registrar(
            $usuarioId,
            'consulta_actualizada'
        );

        return true;
    }

    public function eliminar(
        $rawId,
        int $usuarioId
    ): bool {
        $id = ConsultaSanitizer::sanitizarId($rawId);

        $validacionId = ConsultaValidator::validarSoloIdConsulta(
            $id
        );

        if (!$validacionId['success']) {
            throw new ValidationException(
                $validacionId['errors']
            );
        }

        $this->obtener($id);

        $usuario = $this->usuarioRepository->findById(
            $usuarioId
        );

        if (
            !$usuario
            || !$this->policy->puedeAdministrar(
                $usuario->rol_id
            )
        ) {
            throw new ForbiddenException(
                'No autorizado para eliminar consultas'
            );
        }

        $resultado = $this->consultaRepository->delete($id);

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'consulta_eliminada'
            );
        }

        return $resultado;
    }

    public function listarPorUsuario(
        $rawUsuarioConsultadoId,
        int $usuarioLogueadoId,
        int $rolId
    ): array {
        $usuarioConsultadoId = ConsultaSanitizer::sanitizarId(
            $rawUsuarioConsultadoId
        );

        if (
            !$usuarioConsultadoId
            || $usuarioConsultadoId <= 0
        ) {
            throw new ValidationException([
                'usuario_id' => ['ID de usuario inválido']
            ]);
        }

        if (
            !$this->policy->puedeVerDeUsuario(
                $usuarioLogueadoId,
                $rolId,
                $usuarioConsultadoId
            )
        ) {
            throw new ForbiddenException(
                'No tienes permiso para ver las consultas de este usuario'
            );
        }

        return $this->consultaRepository->getByUsuario(
            $usuarioConsultadoId
        );
    }

    public function listarPorPropiedad(
        $rawPropiedadId,
        int $usuarioId,
        int $rolId
    ): array {
        $propiedadId = ConsultaSanitizer::sanitizarId(
            $rawPropiedadId
        );

        $validacion = ConsultaValidator::validarPropiedadId(
            $propiedadId
        );

        if (!$validacion['success']) {
            throw new ValidationException([
                'propiedad_id' => [
                    $validacion['error']
                ]
            ]);
        }

        $propiedad = $this->propiedadRepository->findById(
            $propiedadId
        );

        if (!$propiedad) {
            throw new NotFoundException(
                'Propiedad no encontrada'
            );
        }

        if (
            !$this->policy->puedeVerDePropiedad(
                $usuarioId,
                $rolId,
                $propiedad
            )
        ) {
            throw new ForbiddenException(
                'No tienes permiso para ver las consultas de esta propiedad'
            );
        }

        return $this->consultaRepository->getByPropiedad(
            $propiedadId
        );
    }
}