<?php

namespace App\Services;

use App\Models\Favorito;
use App\Policies\FavoritoPolicy;
use App\Repositories\FavoritoRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Services\LogActividadService;

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
     *
     * La autorización de qué usuario puede consultar
     * estos favoritos se realiza en el Policy.
     */
    public function obtenerFavoritos(
        int $usuarioId,
        int $usuarioConsultadoId,
        int $rolId
    ): array {
        if ($usuarioId <= 0 || $usuarioConsultadoId <= 0) {
            return [];
        }

        if (!$this->policy->puedeVerDeUsuario(
            $usuarioId,
            $rolId,
            $usuarioConsultadoId
        )) {
            throw new \Exception(
                'No tienes permiso para consultar los favoritos de este usuario',
                403
            );
        }

        return $this->favoritoRepository->getByUserId(
            $usuarioConsultadoId
        );
    }

    /**
     * Obtener los IDs de propiedades favoritas de un usuario.
     *
     * Este método se utiliza internamente para marcar propiedades
     * como favoritas en listados.
     */
    public function obtenerIdsFavoritos(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        return $this->favoritoRepository->getPropiedadIdsByUser($usuarioId);
    }

    /**
     * Verificar si una propiedad es favorita del usuario.
     *
     * Método auxiliar para lógica interna.
     */
    public function esFavorito(
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

    /**
     * Agregar una propiedad a favoritos.
     *
     * La imposibilidad de agregar la propia propiedad
     * es una regla de negocio y permanece en el Service.
     */
    public function agregarFavorito(
        int $usuarioId,
        int $propiedadId
    ): bool {
        if ($usuarioId <= 0) {
            throw new \InvalidArgumentException(
                'ID de usuario inválido',
                400
            );
        }

        if ($propiedadId <= 0) {
            throw new \InvalidArgumentException(
                'ID de propiedad inválido',
                400
            );
        }

        $propiedad = $this->propiedadRepository->findById($propiedadId);

        if (!$propiedad) {
            throw new \Exception(
                'La propiedad no existe',
                404
            );
        }

        if (
            isset($propiedad->usuario_id)
            && (int) $propiedad->usuario_id === $usuarioId
        ) {
            throw new \Exception(
                'No puedes agregar tu propia propiedad a favoritos',
                400
            );
        }

        $resultado = $this->favoritoRepository->add(
            $usuarioId,
            $propiedadId
        );

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'favorito_agregado'
            );
        }

        return $resultado;
    }

    /**
     * Eliminar un favorito.
     *
     * Primero se obtiene el recurso concreto y luego
     * se verifica la autorización mediante FavoritoPolicy.
     */
    public function eliminarFavorito(
        int $usuarioId,
        int $propiedadId
    ): bool {
        if ($usuarioId <= 0) {
            throw new \InvalidArgumentException(
                'ID de usuario inválido',
                400
            );
        }

        if ($propiedadId <= 0) {
            throw new \InvalidArgumentException(
                'ID de propiedad inválido',
                400
            );
        }

        $favorito = $this->favoritoRepository
            ->findByUsuarioAndPropiedad(
                $usuarioId,
                $propiedadId
            );

        if (!$favorito) {
            throw new \Exception(
                'La propiedad no está en favoritos',
                404
            );
        }

        if (!$this->policy->puedeEliminar($usuarioId, $favorito)) {
            throw new \Exception(
                'No tienes permiso para eliminar este favorito',
                403
            );
        }

        $resultado = $this->favoritoRepository->remove(
            $usuarioId,
            $propiedadId
        );

        if ($resultado) {
            $this->logService->registrar(
                $usuarioId,
                'favorito_eliminado'
            );
        }

        return $resultado;
    }

    /**
     * Contar favoritos de una propiedad.
     *
     * No requiere autorización porque se trata de un dato
     * agregado de la propiedad y no de favoritos personales.
     */
    public function contarFavoritos(int $propiedadId): int
    {
        if ($propiedadId <= 0) {
            return 0;
        }

        return $this->favoritoRepository->countByPropiedad(
            $propiedadId
        );
    }

    /**
     * Marcar las propiedades que pertenecen a los favoritos
     * del usuario autenticado.
     */
    public function marcarFavoritosEnListado(
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
                $idsFavoritos
            );
        }

        return $propiedades;
    }
}