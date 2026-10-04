<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Policies\FavoritoPolicy;
use App\Repositories\FavoritoRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Sanitizers\FavoritoSanitizer;
use App\Validators\FavoritoValidator;

class FavoritoService
{
    private FavoritoRepositoryInterface $favoritoRepository;
    private PropiedadRepositoryInterface $propiedadRepository;
    private LogActividadService $logService;
    private FavoritoPolicy $policy;

    public function __construct(
        FavoritoRepositoryInterface $favoritoRepository,
        PropiedadRepositoryInterface $propiedadRepository,
        LogActividadService $logService,
        FavoritoPolicy $policy
    ) {
        $this->favoritoRepository = $favoritoRepository;
        $this->propiedadRepository = $propiedadRepository;
        $this->logService = $logService;
        $this->policy = $policy;
    }

    /**
     * Obtener los favoritos de un usuario.
     */
    public function listar(
        int $usuarioId,
        $rawUsuarioConsultadoId,
        int $rolId
    ): array {
        $usuarioConsultadoId = FavoritoSanitizer::sanitizarId(
            $rawUsuarioConsultadoId
        );

        $validacion = FavoritoValidator::validarSoloId(
            $usuarioConsultadoId
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        if (
            !$this->policy->puedeVerDeUsuario(
                $usuarioId,
                $rolId,
                $usuarioConsultadoId
            )
        ) {
            throw new ForbiddenException(
                'No tienes permiso para consultar los favoritos de este usuario'
            );
        }

        return $this->favoritoRepository->getByUserId(
            $usuarioConsultadoId
        );
    }


    /**
     * Agregar una propiedad a favoritos.
     */
    public function agregar(
        array $rawData,
        int $usuarioId
    ): bool {
        $data = FavoritoSanitizer::sanitizarCrear(
            $rawData
        );

        $validacion = FavoritoValidator::validarCrear(
            $data
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        if ($usuarioId <= 0) {
            throw new BadRequestException(
                'ID de usuario inválido'
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

        if (
            isset($propiedad->usuario_id)
            && (int) $propiedad->usuario_id === $usuarioId
        ) {
            throw new BadRequestException(
                'No puedes agregar tu propia propiedad a favoritos'
            );
        }

        $resultado = $this->favoritoRepository->add(
            $usuarioId,
            $propiedadId
        );

        if (!$resultado) {
            throw new ConflictException(
                'La propiedad ya está en favoritos'
            );
        }

        $this->logService->registrar(
            $usuarioId,
            'favorito_agregado'
        );

        return true;
    }

    /**
     * Eliminar una propiedad de favoritos.
     */
    public function eliminar(
        $rawPropiedadId,
        int $usuarioId
    ): bool {
        $propiedadId = FavoritoSanitizer::sanitizarId(
            $rawPropiedadId
        );

        $validacion = FavoritoValidator::validarSoloId(
            $propiedadId
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        if ($usuarioId <= 0) {
            throw new BadRequestException(
                'ID de usuario inválido'
            );
        }

        $favorito = $this->favoritoRepository
            ->findByUsuarioAndPropiedad(
                $usuarioId,
                $propiedadId
            );

        if (!$favorito) {
            throw new NotFoundException(
                'La propiedad no está en favoritos'
            );
        }

        if (!$this->policy->puedeEliminar(
            $usuarioId,
            $favorito
        )) {
            throw new ForbiddenException(
                'No tienes permiso para eliminar este favorito'
            );
        }

        $resultado = $this->favoritoRepository->remove(
            $usuarioId,
            $propiedadId
        );

        if (!$resultado) {
            throw new NotFoundException(
                'No se pudo eliminar el favorito'
            );
        }

        $this->logService->registrar(
            $usuarioId,
            'favorito_eliminado'
        );

        return true;
    }
}