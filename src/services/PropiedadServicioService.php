<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Policies\PropiedadPolicy;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\PropiedadServicioRepositoryInterface;
use App\Repositories\ServicioRepositoryInterface;
use App\Sanitizers\PropiedadServicioSanitizer;
use App\Validators\PropiedadServicioValidator;
use App\Services\LogActividadService;

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

    public function obtenerServiciosPorPropiedad(int $propiedadId): array
    {
        $propiedadId = PropiedadServicioSanitizer::sanitizarPropiedadId(
            $propiedadId
        );

        $validacion = PropiedadServicioValidator::validarSoloId(
            $propiedadId
        );

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $propiedad = $this->propiedadRepository->findById($propiedadId);

        if (!$propiedad) {
            throw new NotFoundException('La propiedad no existe');
        }

        return $this->propiedadServicioRepository->getByPropiedad(
            $propiedadId
        );
    }

    public function obtenerPropiedadesPorServicio(int $servicioId): array
    {
        $servicioId = PropiedadServicioSanitizer::sanitizarServicioId(
            $servicioId
        );

        $validacion = PropiedadServicioValidator::validarSoloId(
            $servicioId
        );

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $servicio = $this->servicioRepository->findById($servicioId);

        if (!$servicio) {
            throw new NotFoundException('El servicio no existe');
        }

        return $this->propiedadServicioRepository->getByServicio(
            $servicioId
        );
    }

    public function tieneServicio(
        int $propiedadId,
        int $servicioId
    ): bool {
        $data = PropiedadServicioSanitizer::sanitizar([
            'propiedad_id' => $propiedadId,
            'servicio_id' => $servicioId,
        ]);

        $validacion = PropiedadServicioValidator::validarCrear($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        return $this->propiedadServicioRepository->exists(
            $data['propiedad_id'],
            $data['servicio_id']
        );
    }

    public function asignarServicio(
        int $propiedadId,
        int $servicioId,
        int $usuarioId,
        int $rolId
    ): bool {
        $data = PropiedadServicioSanitizer::sanitizar([
            'propiedad_id' => $propiedadId,
            'servicio_id' => $servicioId,
        ]);

        $validacion = PropiedadServicioValidator::validarCrear($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $propiedad = $this->propiedadRepository->findById(
            $data['propiedad_id']
        );

        if (!$propiedad) {
            throw new NotFoundException('La propiedad no existe');
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
            throw new NotFoundException('El servicio no existe');
        }

        $resultado = $this->propiedadServicioRepository->attach(
            $data['propiedad_id'],
            $data['servicio_id']
        );

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'servicio_asignado'
            );
        }

        return $resultado;
    }

    public function desasignarServicio(
        int $propiedadId,
        int $servicioId,
        int $usuarioId,
        int $rolId
    ): bool {
        $data = PropiedadServicioSanitizer::sanitizar([
            'propiedad_id' => $propiedadId,
            'servicio_id' => $servicioId,
        ]);

        $validacion = PropiedadServicioValidator::validarCrear($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $propiedad = $this->propiedadRepository->findById(
            $data['propiedad_id']
        );

        if (!$propiedad) {
            throw new NotFoundException('La propiedad no existe');
        }

        $this->policy->gestionar(
            $propiedad,
            $usuarioId,
            $rolId
        );

        if (!$this->propiedadServicioRepository->exists(
            $data['propiedad_id'],
            $data['servicio_id']
        )) {
            throw new NotFoundException(
                'La propiedad no tiene este servicio asignado'
            );
        }

        $resultado = $this->propiedadServicioRepository->detach(
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

    public function asignarMultiplesServicios(
        int $propiedadId,
        array $servicioIds,
        int $usuarioId,
        int $rolId
    ): array {
        $propiedadId = PropiedadServicioSanitizer::sanitizarPropiedadId(
            $propiedadId
        );

        $servicioIds = PropiedadServicioSanitizer::sanitizarServicioIds(
            $servicioIds
        );

        $validacionPropiedad = PropiedadServicioValidator::validarSoloId(
            $propiedadId
        );

        if (!$validacionPropiedad['success']) {
            throw new ValidationException(
                $validacionPropiedad['errors']
            );
        }

        $validacionServicios = PropiedadServicioValidator::validarServicioIds(
            $servicioIds,
            false
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
            throw new NotFoundException('La propiedad no existe');
        }

        $this->policy->gestionar(
            $propiedad,
            $usuarioId,
            $rolId
        );

        $servicios = $this->servicioRepository->findByIds($servicioIds);

        $idsEncontrados = $servicios
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $idsFaltantes = array_values(
            array_diff($servicioIds, $idsEncontrados)
        );

        if (!empty($idsFaltantes)) {
            throw new NotFoundException(
                'Los siguientes servicios no existen: ' .
                implode(', ', $idsFaltantes)
            );
        }

        $resultados = $this->propiedadServicioRepository->attachMultiple(
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

    public function sincronizarServicios(
        int $propiedadId,
        array $servicioIds,
        int $usuarioId,
        int $rolId
    ): array {
        $propiedadId = PropiedadServicioSanitizer::sanitizarPropiedadId(
            $propiedadId
        );

        $servicioIds = PropiedadServicioSanitizer::sanitizarServicioIds(
            $servicioIds
        );

        $validacionPropiedad = PropiedadServicioValidator::validarSoloId(
            $propiedadId
        );

        if (!$validacionPropiedad['success']) {
            throw new ValidationException(
                $validacionPropiedad['errors']
            );
        }

        $validacionServicios = PropiedadServicioValidator::validarServicioIds(
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
            throw new NotFoundException('La propiedad no existe');
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
                array_diff($servicioIds, $idsEncontrados)
            );

            if (!empty($idsFaltantes)) {
                throw new NotFoundException(
                    'Los siguientes servicios no existen: ' .
                    implode(', ', $idsFaltantes)
                );
            }
        }

        $resultados = $this->propiedadServicioRepository->sync(
            $propiedadId,
            $servicioIds
        );

        $this->logService->registrar(
            $usuarioId,
            'servicios_sincronizados'
        );

        return $resultados;
    }

    public function obtenerIdsServiciosPorPropiedad(
        int $propiedadId
    ): array {
        $propiedadId = PropiedadServicioSanitizer::sanitizarPropiedadId(
            $propiedadId
        );

        $validacion = PropiedadServicioValidator::validarSoloId(
            $propiedadId
        );

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $propiedad = $this->propiedadRepository->findById(
            $propiedadId
        );

        if (!$propiedad) {
            throw new NotFoundException('La propiedad no existe');
        }

        return $this->propiedadServicioRepository
            ->getServicioIdsByPropiedad($propiedadId);
    }
}