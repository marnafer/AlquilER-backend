<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
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

        if (!$this->reservaRepository->findById($reservaId)) {
            throw new NotFoundException(
                'Reserva no encontrada'
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

        $reserva = $this->reservaRepository->findById(
            (int) $data['reserva_id']
        );

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

    public function actualizar(
        $rawId,
        array $rawData,
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

        $resena = $this->repository->findById($id);

        if (!$resena) {
            throw new NotFoundException(
                'Reseña no encontrada'
            );
        }

        if ($rolId !== 2 && (int) $resena->calificador_id !== $usuarioId) {
            throw new ForbiddenException(
                'No tienes permiso para editar esta reseña'
            );
        }

        $data = ResenaSanitizer::sanitizarActualizar(
            $rawData
        );

        $cambios = [];

        if ($data['calificacion'] !== null) {
            $resultado = ResenaValidator::validarCalificacion(
                $data['calificacion']
            );

            if (!$resultado['success']) {
                throw new ValidationException([
                    'calificacion' => [
                        $resultado['error']
                    ]
                ]);
            }

            $cambios['calificacion'] = $data['calificacion'];
        }

        if ($data['comentario'] !== null) {
            $resultado = ResenaValidator::validarComentario(
                $data['comentario']
            );

            if (!$resultado['success']) {
                throw new ValidationException([
                    'comentario' => [
                        $resultado['error']
                    ]
                ]);
            }

            $cambios['comentario'] = $data['comentario'];
        }

        if (empty($cambios)) {
            throw new BadRequestException(
                'No hay datos para actualizar'
            );
        }

        $this->repository->update(
            $resena,
            $cambios
        );

        $this->logActividadService->registrar(
            $usuarioId,
            'Actualización de reseña'
        );
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