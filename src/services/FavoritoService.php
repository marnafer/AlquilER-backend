<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Policies\FavoritoPolicy;
use App\Repositories\FavoritoRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;

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

    public function listar(
        int $usuarioId,
        int $usuarioConsultadoId,
        int $rolId
    ): array {
        if ($usuarioId <= 0 || $usuarioConsultadoId <= 0) {
            return [];
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

    public function listarIds(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        return $this->favoritoRepository->getPropiedadIdsByUser(
            $usuarioId
        );
    }

    public function verificar(
        int $usuarioId,
        int $propiedadId
    ): bool {
        if ($usuarioId <= 0 || $propiedadId <= 0) {
            return false;
        }

        return $this->favoritoRepository->exists(
            $usuarioId,
            $propiedadId
        );
    }

    public function agregar(
        int $usuarioId,
        int $propiedadId
    ): bool {
        if ($usuarioId <= 0) {
            throw new BadRequestException(
                'ID de usuario inválido'
            );
        }

        if ($propiedadId <= 0) {
            throw new BadRequestException(
                'ID de propiedad inválido'
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

    public function eliminar(
        int $usuarioId,
        int $propiedadId
    ): bool {
        if ($usuarioId <= 0) {
            throw new BadRequestException(
                'ID de usuario inválido'
            );
        }

        if ($propiedadId <= 0) {
            throw new BadRequestException(
                'ID de propiedad inválido'
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

        if (!$this->policy->puedeEliminar($usuarioId, $favorito)) {
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

    public function contar(int $propiedadId): int
    {
        if ($propiedadId <= 0) {
            return 0;
        }

        return $this->favoritoRepository->countByPropiedad(
            $propiedadId
        );
    }

    public function marcarEnListado(
        int $usuarioId,
        array $propiedades
    ): array {
        if (empty($propiedades) || $usuarioId <= 0) {
            return $propiedades;
        }

        $idsFavoritos = $this->favoritoRepository
            ->getPropiedadIdsByUser($usuarioId);

        foreach ($propiedades as &$propiedad) {
            $propiedad['es_favorito'] = in_array(
                $propiedad['id'] ?? null,
                $idsFavoritos,
                true
            );
        }

        return $propiedades;
    }
}