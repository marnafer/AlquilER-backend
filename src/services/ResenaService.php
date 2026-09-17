<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Models\Resena;
use App\Repositories\ResenaRepositoryInterface;
use App\Repositories\ReservaRepositoryInterface;
use App\Policies\ResenaPolicy;
use App\Sanitizers\ResenaSanitizer;
use App\Validators\ResenaValidator;
use Illuminate\Database\Eloquent\Collection;

class ResenaService
{
    public function __construct(
        private readonly ResenaRepositoryInterface $repository,
        private readonly ReservaRepositoryInterface $reservaRepository,
        private readonly ResenaPolicy $policy,
        private readonly LogActividadService $logActividadService
    ) {
    }

    public function listar(array $filtros = []): array
    {
        $resenas = $this->repository->all($filtros);

        return [
            'items' => $resenas,
            'total' => $resenas->count(),
        ];
    }

    public function obtener($rawId): Resena
    {
        $id = ResenaSanitizer::sanitizarId($rawId);

        $validacion = ResenaValidator::validarSoloId($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $resena = $this->repository->findById($id);

        if (!$resena) {
            throw new NotFoundException(
                'Reseña no encontrada'
            );
        }

        return $resena;
    }

    public function obtenerPorReserva($rawReservaId): Collection
    {
        $reservaId = ResenaSanitizer::sanitizarId(
            $rawReservaId
        );

        $validacion = ResenaValidator::validarReservaId(
            $reservaId
        );

        if (!$validacion['success']) {
            throw new ValidationException([
                'reserva_id' => [
                    $validacion['error']
                ]
            ]);
        }
<<<<<<< HEAD
        
        // Verificar permisos (solo el calificador o admin)
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario || ($usuario->rol_id != 2 && $resena->calificador_id != $usuarioId)) {
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
=======

        if (!$this->reservaRepository->findById($reservaId)) {
            throw new NotFoundException(
                'Reserva no encontrada'
>>>>>>> fba7cc3816277d17ecfffab65957c9e1cd3c7f5d
            );
        }

        return $this->repository->getByReserva(
            $reservaId
        );
    }

    public function obtenerPorPropiedad($rawPropiedadId): Collection
    {
        $propiedadId = ResenaSanitizer::sanitizarId(
            $rawPropiedadId
        );

        $validacion = ResenaValidator::validarPropiedadId(
            $propiedadId
        );

        if (!$validacion['success']) {
            throw new ValidationException([
                'propiedad_id' => [
                    $validacion['error']
                ]
            ]);
        }
<<<<<<< HEAD
        
        // Solo admin puede eliminar
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario || $usuario->rol_id != 2) {
            throw new \Exception("No autorizado", 403);
=======

        return $this->repository->getByPropiedad(
            $propiedadId
        );
    }

    public function obtenerPorUsuario($rawUsuarioId): Collection
    {
        $usuarioId = ResenaSanitizer::sanitizarId(
            $rawUsuarioId
        );

        $validacion = ResenaValidator::validarUsuarioId(
            $usuarioId
        );

        if (!$validacion['success']) {
            throw new ValidationException([
                'usuario_id' => [
                    $validacion['error']
                ]
            ]);
>>>>>>> fba7cc3816277d17ecfffab65957c9e1cd3c7f5d
        }

        return $this->repository->getByUsuario(
            $usuarioId
        );
    }

    public function obtenerPorCalificador($rawCalificadorId): Collection
    {
        $calificadorId = ResenaSanitizer::sanitizarId(
            $rawCalificadorId
        );

        $validacion = ResenaValidator::validarCalificadorId(
            $calificadorId
        );

        if (!$validacion['success']) {
            throw new ValidationException([
                'calificador_id' => [
                    $validacion['error']
                ]
            ]);
        }

        return $this->repository->getByCalificador(
            $calificadorId
        );
    }

    public function crear(
        array $rawData,
        int $usuarioId
    ): Resena {
        $data = ResenaSanitizer::sanitizarCrear(
            $rawData
        );

        $validacion = ResenaValidator::validarCrear(
            $data
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }
<<<<<<< HEAD
        
        return $resultado;
    }
    
    public function restaurarResena(int $id, int $usuarioId): bool
    {
        $usuario = $this->usuarioRepository->findById($usuarioId);
        if (!$usuario || $usuario->rol_id != 2) {
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
=======

        $reserva = $this->reservaRepository->findById(
            (int) $data['reserva_id']
        );

>>>>>>> fba7cc3816277d17ecfffab65957c9e1cd3c7f5d
        if (!$reserva) {
            throw new NotFoundException(
                'Reserva no encontrada'
            );
        }

        if ($reserva->estado !== 'finalizada') {
            throw new BadRequestException(
                'Solo se puede crear una reseña para una reserva finalizada'
            );
        }

        $this->policy->crear(
            $reserva,
            $usuarioId,
            $data['tipo']
        );

        if (
            $this->repository->existePorReservaYTipo(
                (int) $data['reserva_id'],
                $data['tipo']
            )
        ) {
            throw new ConflictException(
                'Ya existe una reseña de este tipo para la reserva'
            );
        }

        $data['calificador_id'] = $usuarioId;

        $resena = $this->repository->create(
            $data
        );

        $this->logActividadService->registrar(
            $usuarioId,
            'Creación de reseña'
        );

        return $resena;
    }

    public function eliminar(
        $rawId,
        int $usuarioId,
        int $rolId
    ): void {
        $resena = $this->obtener($rawId);

        $this->policy->eliminar(
            $resena,
            $usuarioId,
            $rolId
        );

        $this->repository->delete(
            $resena
        );

        $this->logActividadService->registrar(
            $usuarioId,
            'Eliminación de reseña'
        );
    }

    // Futura ruta administrativa
    public function restaurar(
        $rawId,
        int $usuarioId,
        int $rolId
    ): void {
        $id = ResenaSanitizer::sanitizarId(
            $rawId
        );

        $validacion = ResenaValidator::validarSoloId(
            $id
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $resena = $this->repository->findDeletedById(
            $id
        );

        if (!$resena) {
            throw new NotFoundException(
                'Reseña eliminada no encontrada'
            );
        }

        $this->policy->restaurar(
            $rolId
        );

        $this->repository->restore(
            $resena
        );

        $this->logActividadService->registrar(
            $usuarioId,
            'Restauración de reseña'
        );
    }

    public function promedioPropiedad(
        $rawPropiedadId
    ): float {
        $propiedadId = ResenaSanitizer::sanitizarId(
            $rawPropiedadId
        );

        $validacion = ResenaValidator::validarPropiedadId(
            $propiedadId
        );

        if (!$validacion['success']) {
            throw new ValidationException([
                'propiedad_id' => [
                    $validacion['error']
                ]
            ]);
        }

        return $this->repository->getPromedioByPropiedad(
            $propiedadId
        );
    }

    public function promedioUsuario(
        $rawUsuarioId
    ): float {
        $usuarioId = ResenaSanitizer::sanitizarId(
            $rawUsuarioId
        );

        $validacion = ResenaValidator::validarUsuarioId(
            $usuarioId
        );

        if (!$validacion['success']) {
            throw new ValidationException([
                'usuario_id' => [
                    $validacion['error']
                ]
            ]);
        }

        return $this->repository->getPromedioByUsuario(
            $usuarioId
        );
    }
}