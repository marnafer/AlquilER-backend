<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\ConflictException;
use App\Models\Propiedad;
use App\Policies\PropiedadPolicy;
use App\Repositories\CategoriaRepositoryInterface;
use App\Repositories\LocalidadRepositoryInterface;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\ReservaRepositoryInterface;
use App\Sanitizers\PropiedadSanitizer;
use App\Validators\PropiedadValidator;

class PropiedadService
{
    public function __construct(
        private readonly PropiedadRepositoryInterface $repository,
        private readonly LogActividadService $logActividadService,
        private readonly CategoriaRepositoryInterface $categoriaRepository,
        private readonly LocalidadRepositoryInterface $localidadRepository,
        private readonly ReservaRepositoryInterface $reservaRepository,
        private readonly PropiedadPolicy $policy
    ) {
    }

    public function listar(): array
    {
        $propiedades = $this->repository->all();

        return [
            'items' => $propiedades,
            'total' => $propiedades->count(),
        ];
    }

    public function misPropiedades(int $usuarioId): array
    {
        $propiedades = $this->repository->porUsuario($usuarioId);

        return [
            'items' => $propiedades,
            'total' => $propiedades->count(),
        ];
    }

    public function obtener($rawId): Propiedad
    {
        $id = PropiedadSanitizer::sanitizarId($rawId);

        $validacion = PropiedadValidator::validarSoloId($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $propiedad = $this->repository->findById($id);

        if (!$propiedad) {
            throw new NotFoundException(
                'Propiedad no encontrada'
            );
        }

        return $propiedad;
    }

    public function obtenerParaActualizar(int $id): Propiedad
    {
        $propiedad = $this->repository->findByIdForUpdate($id);

        if (!$propiedad) {
            throw new NotFoundException(
                'Propiedad no encontrada'
            );
        }

        return $propiedad;
    }

    public function crear(
        array $rawData,
        int $usuarioId
    ): Propiedad {
        $data = PropiedadSanitizer::sanitizarCrear(
            $rawData
        );

        $data['usuario_id'] = $usuarioId;

        $validacion = PropiedadValidator::validar(
            $data
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        if (
            !$this->categoriaRepository->findById(
                (int) $data['categoria_id']
            )
        ) {
            throw new ValidationException([
                'categoria_id' => [
                    'La categoría seleccionada no existe'
                ],
            ]);
        }

        if (
            !$this->localidadRepository->findById(
                (int) $data['localidad_id']
            )
        ) {
            throw new ValidationException([
                'localidad_id' => [
                    'La localidad seleccionada no existe'
                ],
            ]);
        }

        $propiedad = $this->repository->create(
            $data
        );

        $this->logActividadService->registrar(
            $usuarioId,
            'Creación de propiedad'
        );

        return $propiedad;
    }

    public function actualizar(
        int $usuarioId,
        int $rolId,
        $propiedadId,
        array $rawData
    ): void {
        if ($rawData === []) {
            throw new BadRequestException(
                'Debe enviar al menos un campo para actualizar'
            );
        }

        $propiedad = $this->obtener(
            $propiedadId
        );

        $this->policy->gestionar(
            $propiedad,
            $usuarioId,
            $rolId
        );

        unset(
            $rawData['id'],
            $rawData['deleted_at'],
            $rawData['usuario_id']
        );

        $camposPermitidos = [
            'titulo',
            'descripcion',
            'precio',
            'expensas',
            'direccion',
            'cantidad_ambientes',
            'cantidad_dormitorios',
            'cantidad_banos',
            'capacidad',
            'disponible',
            'categoria_id',
            'localidad_id',
        ];

        $datosRecibidos = array_intersect_key(
            $rawData,
            array_flip($camposPermitidos)
        );

        if ($datosRecibidos === []) {
            throw new BadRequestException(
                'No se enviaron campos actualizables'
            );
        }

        $data = PropiedadSanitizer::sanitizarActualizar(
            $datosRecibidos
        );

        $estadoFinal = [
            'titulo' => $propiedad->titulo,
            'descripcion' => $propiedad->descripcion,
            'precio' => $propiedad->precio,
            'expensas' => $propiedad->expensas,
            'direccion' => $propiedad->direccion,
            'cantidad_ambientes' =>
                $propiedad->cantidad_ambientes,
            'cantidad_dormitorios' =>
                $propiedad->cantidad_dormitorios,
            'cantidad_banos' =>
                $propiedad->cantidad_banos,
            'capacidad' => $propiedad->capacidad,
            'disponible' => (int) $propiedad->disponible,
            'categoria_id' => $propiedad->categoria_id,
            'localidad_id' => $propiedad->localidad_id,
        ];

        $estadoFinal = array_merge(
            $estadoFinal,
            $data
        );

        $validacion = PropiedadValidator::validar(
            $estadoFinal
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        if (
            array_key_exists('categoria_id', $data)
            && !$this->categoriaRepository->findById(
                (int) $estadoFinal['categoria_id']
            )
        ) {
            throw new ValidationException([
                'categoria_id' => [
                    'La categoría seleccionada no existe'
                ],
            ]);
        }

        if (
            array_key_exists('localidad_id', $data)
            && !$this->localidadRepository->findById(
                (int) $estadoFinal['localidad_id']
            )
        ) {
            throw new ValidationException([
                'localidad_id' => [
                    'La localidad seleccionada no existe'
                ],
            ]);
        }

        $this->repository->update(
            $propiedad,
            $data
        );

        $this->logActividadService->registrar(
            $usuarioId,
            'Actualización de propiedad'
        );
    }

    public function eliminar(
        int $usuarioId,
        int $rolId,
        $propiedadId
    ): void {
        $propiedad = $this->obtener(
            $propiedadId
        );

        $this->policy->gestionar(
            $propiedad,
            $usuarioId,
            $rolId
        );

        if (
            $this->reservaRepository->tieneReservaActiva(
                $propiedad->id
            )
        ) {
            throw new ConflictException(
                'No se puede eliminar la propiedad porque tiene una reserva activa'
            );
        }

        $this->repository->delete(
            $propiedad
        );

        $this->logActividadService->registrar(
            $usuarioId,
            'Eliminación de propiedad'
        );
    }

    public function restaurar(
        int $usuarioId,
        int $rolId,
        $propiedadId
    ): void {
        $id = PropiedadSanitizer::sanitizarId(
            $propiedadId
        );

        $validacion = PropiedadValidator::validarSoloId(
            $id
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $propiedad = $this->repository->findDeletedById(
            $id
        );

        if (!$propiedad) {
            throw new NotFoundException(
                'Propiedad no encontrada'
            );
        }

        $this->policy->gestionar(
            $propiedad,
            $usuarioId,
            $rolId
        );

        $this->repository->restore(
            $propiedad
        );

        $this->logActividadService->registrar(
            $usuarioId,
            'Restauración de propiedad'
        );
    }
}