<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Policies\PropiedadPolicy;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\PropiedadServicioRepositoryInterface;
use App\Repositories\ServicioRepositoryInterface;
use App\Sanitizers\PropiedadServicioSanitizer;
use App\Validators\PropiedadServicioValidator;

class PropiedadServicioService
{
    public function __construct(
        private readonly PropiedadServicioRepositoryInterface $propiedadServicioRepository,
        private readonly PropiedadRepositoryInterface $propiedadRepository,
        private readonly ServicioRepositoryInterface $servicioRepository,
        private readonly LogActividadService $logService,
        private readonly PropiedadPolicy $policy
    ) {
    }

    public function listar($rawPropiedadId): array
    {
        $propiedadId =
            PropiedadServicioSanitizer::sanitizarId(
                $rawPropiedadId
            );

        $validacion =
            PropiedadServicioValidator::validarPropiedadId(
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
                'La propiedad no existe'
            );
        }

        return $this->propiedadServicioRepository
            ->getByPropiedad($propiedadId);
    }

    public function listarPorServicio($rawServicioId): array
    {
        $servicioId =
            PropiedadServicioSanitizer::sanitizarId(
                $rawServicioId
            );

        $validacion =
            PropiedadServicioValidator::validarServicioId(
                $servicioId
            );

        if (!$validacion['success']) {
            throw new ValidationException([
                'servicio_id' => [
                    $validacion['error']
                ]
            ]);
        }

        $servicio = $this->servicioRepository->findById(
            $servicioId
        );

        if (!$servicio) {
            throw new NotFoundException(
                'El servicio no existe'
            );
        }

        return $this->propiedadServicioRepository
            ->getByServicio($servicioId);
    }

    public function tiene(
        $rawPropiedadId,
        $rawServicioId
    ): bool {
        $data = PropiedadServicioSanitizer::sanitizar([
            'propiedad_id' => $rawPropiedadId,
            'servicio_id' => $rawServicioId,
        ]);

        $validacion =
            PropiedadServicioValidator::validarCrear($data);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        return $this->propiedadServicioRepository->exists(
            $data['propiedad_id'],
            $data['servicio_id']
        );
    }

   public function asignar(
    $rawPropiedadId,
    $rawServicioId,
    int $usuarioId,
    int $rolId
    ): array {
        $data = PropiedadServicioSanitizer::sanitizar([
            'propiedad_id' => $rawPropiedadId,
            'servicio_id' => $rawServicioId,
        ]);

        $validacion =
            PropiedadServicioValidator::validarCrear($data);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $propiedad = $this->propiedadRepository->findById(
            $data['propiedad_id']
        );

        if (!$propiedad) {
            throw new NotFoundException(
                'La propiedad no existe'
            );
        }

        $this->policy->gestionar(
            $propiedad,
            $usuarioId,
            $rolId
        );

        $servicio = $this->servicioRepository->findById(
            $data['servicio_id']
        );

        if (!$servicio) {
            throw new NotFoundException(
                'El servicio no existe'
            );
        }

        if (
            $this->propiedadServicioRepository->exists(
                $data['propiedad_id'],
                $data['servicio_id']
            )
        ) {
            throw new ConflictException(
                'La propiedad ya tiene este servicio asignado'
            );
        }

        $resultado =
            $this->propiedadServicioRepository->attach(
                $data['propiedad_id'],
                $data['servicio_id']
            );

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'servicio_asignado'
            );
        }

        return [
            'propiedad_id' => $data['propiedad_id'],
            'servicio_id' => $data['servicio_id'],
        ];
    }

    public function desasignar(
        $rawPropiedadId,
        $rawServicioId,
        int $usuarioId,
        int $rolId
    ): bool {
        $data = PropiedadServicioSanitizer::sanitizar([
            'propiedad_id' => $rawPropiedadId,
            'servicio_id' => $rawServicioId,
        ]);

        $validacion =
            PropiedadServicioValidator::validarCrear($data);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $propiedad = $this->propiedadRepository->findById(
            $data['propiedad_id']
        );

        if (!$propiedad) {
            throw new NotFoundException(
                'La propiedad no existe'
            );
        }

        $this->policy->gestionar(
            $propiedad,
            $usuarioId,
            $rolId
        );

        if (
            !$this->propiedadServicioRepository->exists(
                $data['propiedad_id'],
                $data['servicio_id']
            )
        ) {
            throw new NotFoundException(
                'La propiedad no tiene este servicio asignado'
            );
        }

        $resultado =
            $this->propiedadServicioRepository->detach(
                $data['propiedad_id'],
                $data['servicio_id']
            );

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'servicio_desasignado'
            );
        }

        return $resultado;
    }

    public function asignarMultiples(
        $rawPropiedadId,
        array $rawServicioIds,
        int $usuarioId,
        int $rolId
    ): array {
        $propiedadId =
            PropiedadServicioSanitizer::sanitizarId(
                $rawPropiedadId
            );

        $servicioIds =
            PropiedadServicioSanitizer::sanitizarServicioIds(
                $rawServicioIds
            );

        $validacionPropiedad =
            PropiedadServicioValidator::validarPropiedadId(
                $propiedadId
            );

        if (!$validacionPropiedad['success']) {
            throw new ValidationException([
                'propiedad_id' => [
                    $validacionPropiedad['error']
                ]
            ]);
        }

        $validacionServicios =
            PropiedadServicioValidator::validarServicioIds(
                $servicioIds
            );

        if (!$validacionServicios['success']) {
            throw new ValidationException(
                $validacionServicios['errors']
            );
        }

        $propiedad = $this->propiedadRepository->findById(
            $propiedadId
        );

        if (!$propiedad) {
            throw new NotFoundException(
                'La propiedad no existe'
            );
        }

        $this->policy->gestionar(
            $propiedad,
            $usuarioId,
            $rolId
        );

        $servicios = $this->servicioRepository->findByIds(
            $servicioIds
        );

        $idsEncontrados = $servicios
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $idsFaltantes = array_values(
            array_diff(
                $servicioIds,
                $idsEncontrados
            )
        );

        if (!empty($idsFaltantes)) {
            throw new NotFoundException(
                'Los siguientes servicios no existen: ' .
                implode(', ', $idsFaltantes)
            );
        }

        $resultados =
            $this->propiedadServicioRepository->attachMultiple(
                $propiedadId,
                $servicioIds
            );

        if (!empty($resultados['asignados'])) {
            $this->logService->registrar(
                $usuarioId,
                'servicios_multiples_asignados'
            );
        }

        return $resultados;
    }

    public function sincronizar(
        $rawPropiedadId,
        array $rawServicioIds,
        int $usuarioId,
        int $rolId
    ): array {
        $propiedadId =
            PropiedadServicioSanitizer::sanitizarId(
                $rawPropiedadId
            );

        $servicioIds =
            PropiedadServicioSanitizer::sanitizarServicioIds(
                $rawServicioIds
            );

        $validacionPropiedad =
            PropiedadServicioValidator::validarPropiedadId(
                $propiedadId
            );

        if (!$validacionPropiedad['success']) {
            throw new ValidationException([
                'propiedad_id' => [
                    $validacionPropiedad['error']
                ]
            ]);
        }

        $validacionServicios =
            PropiedadServicioValidator::validarServicioIds(
                $servicioIds,
                true
            );

        if (!$validacionServicios['success']) {
            throw new ValidationException(
                $validacionServicios['errors']
            );
        }

        $propiedad = $this->propiedadRepository->findById(
            $propiedadId
        );

        if (!$propiedad) {
            throw new NotFoundException(
                'La propiedad no existe'
            );
        }

        $this->policy->gestionar(
            $propiedad,
            $usuarioId,
            $rolId
        );

        if (!empty($servicioIds)) {
            $servicios = $this->servicioRepository->findByIds(
                $servicioIds
            );

            $idsEncontrados = $servicios
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $idsFaltantes = array_values(
                array_diff(
                    $servicioIds,
                    $idsEncontrados
                )
            );

            if (!empty($idsFaltantes)) {
                throw new NotFoundException(
                    'Los siguientes servicios no existen: ' .
                    implode(', ', $idsFaltantes)
                );
            }
        }

        $resultados =
            $this->propiedadServicioRepository->sync(
                $propiedadId,
                $servicioIds
            );

        $this->logService->registrar(
            $usuarioId,
            'servicios_sincronizados'
        );

        return $resultados;
    }

    public function obtenerIds($rawPropiedadId): array
    {
        $propiedadId =
            PropiedadServicioSanitizer::sanitizarId(
                $rawPropiedadId
            );

        $validacion =
            PropiedadServicioValidator::validarPropiedadId(
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
                'La propiedad no existe'
            );
        }

        return $this->propiedadServicioRepository
            ->getServicioIdsByPropiedad($propiedadId);
    }
}