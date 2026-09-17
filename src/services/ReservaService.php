<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Policies\ReservaPolicy;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\ReservaRepositoryInterface;
use App\Sanitizers\ReservaSanitizer;
use App\Validators\ReservaValidator;

class ReservaService
{
    public function __construct(
        private readonly ReservaRepositoryInterface $reservaRepository,
        private readonly PropiedadRepositoryInterface $propiedadRepository,
        private readonly ReservaPolicy $policy,
        private readonly LogActividadService $logService
    ) {
    }

    public function listar(
        int $usuarioId,
        int $rolId,
        array $filtros = []
    ): array {
        if ($rolId === 2) {
            return $this->reservaRepository->getAll($filtros);
        }

        $reservasUsuario = $this->reservaRepository
            ->getByUsuario($usuarioId);

        $propiedades = $this->propiedadRepository
            ->porUsuario($usuarioId);

        $reservasPropiedades = [];

        foreach ($propiedades as $propiedad) {
            $reservasPropiedades = array_merge(
                $reservasPropiedades,
                $this->reservaRepository->getByPropiedad(
                    (int) $propiedad->id
                )
            );
        }

        return collect(
            array_merge(
                $reservasUsuario,
                $reservasPropiedades
            )
        )
            ->unique('id')
            ->sortByDesc('fecha_reserva')
            ->values()
            ->all();
    }

    public function obtener(
        $rawId,
        int $usuarioId,
        int $rolId
    ) {
        $id = ReservaSanitizer::sanitizarId($rawId);

        $validacion = ReservaValidator::validarSoloId($id);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $reserva = $this->reservaRepository->findById($id);

        if (!$reserva) {
            throw new NotFoundException(
                'Reserva no encontrada'
            );
        }

        if (!$this->policy->puedeVer(
            $usuarioId,
            $rolId,
            $reserva
        )) {
            throw new ForbiddenException(
                'No tienes permiso para consultar esta reserva'
            );
        }

        return $reserva;
    }

    public function crear(
        array $rawData,
        int $usuarioId
    ): int {
        $data = ReservaSanitizer::sanitizarCrear($rawData);

        $validacion = ReservaValidator::validarCrear($data);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $propiedadId = (int) $data['propiedad_id'];

        $propiedad = $this->propiedadRepository
            ->findById($propiedadId);

        if (!$propiedad) {
            throw new NotFoundException(
                'La propiedad no existe'
            );
        }

        $propietarioId = (int) $propiedad->usuario_id;

        if (!$this->policy->puedeCrear(
            $usuarioId,
            $propietarioId
        )) {
            throw new ForbiddenException(
                'No puedes reservar una propiedad propia'
            );
        }

        if (!$propiedad->disponible) {
            throw new ConflictException(
                'La propiedad no está disponible para alquiler'
            );
        }

        $data['usuario_id'] = $usuarioId;
        $data['estado'] = 'pendiente';

        $id = $this->reservaRepository->create($data);

        $this->logService->registrar(
            $usuarioId,
            'reserva_creada'
        );

        return $id;
    }

    public function confirmar(
        $rawId,
        int $usuarioId,
        int $rolId
    ): bool {
        $reserva = $this->obtener(
            $rawId,
            $usuarioId,
            $rolId
        );

        if (!$this->policy->puedeConfirmar(
            $usuarioId,
            $rolId,
            $reserva
        )) {
            throw new ForbiddenException(
                'No tienes permiso para confirmar esta reserva'
            );
        }

        if ($reserva->estado !== 'pendiente') {
            throw new BadRequestException(
                'Solo se puede confirmar una reserva pendiente'
            );
        }

        $resultado = $this->reservaRepository->update(
            $reserva->id,
            [
                'estado' => 'confirmada',
                'fecha_confirmacion' => date('Y-m-d H:i:s')
            ]
        );

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'reserva_confirmada'
            );
        }

        return $resultado;
    }

    public function rechazar(
        $rawId,
        int $usuarioId,
        int $rolId
    ): bool {
        $reserva = $this->obtener(
            $rawId,
            $usuarioId,
            $rolId
        );

        if (!$this->policy->puedeRechazar(
            $usuarioId,
            $rolId,
            $reserva
        )) {
            throw new ForbiddenException(
                'No tienes permiso para rechazar esta reserva'
            );
        }

        if ($reserva->estado !== 'pendiente') {
            throw new BadRequestException(
                'Solo se puede rechazar una reserva pendiente'
            );
        }

        $resultado = $this->reservaRepository->update(
            $reserva->id,
            [
                'estado' => 'rechazada',
                'fecha_confirmacion' => null
            ]
        );

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'reserva_rechazada'
            );
        }

        return $resultado;
    }

    public function finalizar(
        $rawId,
        int $usuarioId,
        int $rolId
    ): bool {
        $reserva = $this->obtener(
            $rawId,
            $usuarioId,
            $rolId
        );

        if (!$this->policy->puedeFinalizar(
            $usuarioId,
            $rolId,
            $reserva
        )) {
            throw new ForbiddenException(
                'No tienes permiso para finalizar esta reserva'
            );
        }

        if ($reserva->estado !== 'confirmada') {
            throw new BadRequestException(
                'Solo se puede finalizar una reserva confirmada'
            );
        }

        $resultado = $this->reservaRepository->update(
            $reserva->id,
            [
                'estado' => 'finalizada'
            ]
        );

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'reserva_finalizada'
            );
        }

        return $resultado;
    }

    public function cancelar(
        $rawId,
        int $usuarioId,
        int $rolId
    ): bool {
        $reserva = $this->obtener(
            $rawId,
            $usuarioId,
            $rolId
        );

        if (!$this->policy->puedeCancelar(
            $usuarioId,
            $rolId,
            $reserva
        )) {
            throw new ForbiddenException(
                'No tienes permiso para cancelar esta reserva'
            );
        }

        if (
            !in_array(
                $reserva->estado,
                ['pendiente', 'confirmada'],
                true
            )
        ) {
            throw new BadRequestException(
                'Solo se puede cancelar una reserva pendiente o confirmada'
            );
        }

        $resultado = $this->reservaRepository->update(
            $reserva->id,
            [
                'estado' => 'cancelada'
            ]
        );

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'reserva_cancelada'
            );
        }

        return $resultado;
    }

    public function actualizar(
        $rawId,
        array $rawData,
        int $usuarioId,
        int $rolId
    ): bool {
        if (!$this->policy->puedeModificar($rolId)) {
            throw new ForbiddenException(
                'Solo un administrador puede modificar reservas'
            );
        }

        $id = ReservaSanitizer::sanitizarId($rawId);

        $validacionId = ReservaValidator::validarSoloId($id);

        if (!$validacionId['success']) {
            throw new ValidationException(
                $validacionId['errors']
            );
        }

        $data = ReservaSanitizer::sanitizarActualizarEstado(
            $rawData
        );

        $validacion = ReservaValidator::validarActualizarEstado(
            $data
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $reserva = $this->reservaRepository->findById($id);

        if (!$reserva) {
            throw new NotFoundException(
                'Reserva no encontrada'
            );
        }

        $resultado = $this->reservaRepository->update(
            $id,
            [
                'estado' => $data['estado'],
                'fecha_confirmacion' =>
                    $data['estado'] === 'confirmada'
                        ? date('Y-m-d H:i:s')
                        : null
            ]
        );

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'reserva_actualizada'
            );
        }

        return $resultado;
    }

    public function eliminar(
        $rawId,
        int $usuarioId,
        int $rolId
    ): bool {
        if (!$this->policy->puedeEliminar($rolId)) {
            throw new ForbiddenException(
                'Solo un administrador puede eliminar reservas'
            );
        }

        $id = ReservaSanitizer::sanitizarId($rawId);

        $validacion = ReservaValidator::validarSoloId($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $reserva = $this->reservaRepository->findById($id);

        if (!$reserva) {
            throw new NotFoundException(
                'Reserva no encontrada'
            );
        }

        $resultado = $this->reservaRepository->delete($id);

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'reserva_eliminada'
            );
        }

        return $resultado;
    }

    public function restaurar(
        $rawId,
        int $usuarioId,
        int $rolId
    ): bool {
        if (!$this->policy->puedeRestaurar($rolId)) {
            throw new ForbiddenException(
                'Solo un administrador puede restaurar reservas'
            );
        }

        $id = ReservaSanitizer::sanitizarId($rawId);

        $validacion = ReservaValidator::validarSoloId($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $reserva = $this->reservaRepository
            ->findById($id);

        if ($reserva) {
            throw new ConflictException(
                'La reserva no está eliminada'
            );
        }

        $resultado = $this->reservaRepository->restore($id);

        if (!$resultado) {
            throw new NotFoundException(
                'Reserva eliminada no encontrada'
            );
        }

        $this->logService->registrar(
            $usuarioId,
            'reserva_restaurada'
        );

        return true;
    }
}