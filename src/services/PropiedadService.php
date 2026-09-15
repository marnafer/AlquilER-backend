<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Propiedad;
use App\Repositories\PropiedadRepositoryInterface;
use App\Repositories\CategoriaRepositoryInterface;
use App\Repositories\LocalidadRepositoryInterface;
use App\Sanitizers\PropiedadSanitizer;
use App\Validators\PropiedadValidator;
use App\Policies\PropiedadPolicy;

class PropiedadService
{
    public function __construct(
        private readonly PropiedadRepositoryInterface $repository,
        private readonly LogActividadService $logActividadService,
        private readonly CategoriaRepositoryInterface $categoriaRepository,
        private readonly LocalidadRepositoryInterface $localidadRepository,
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
        $id = PropiedadSanitizer::sanitizarIdPropiedad($rawId);

        $validacion = PropiedadValidator::validarSoloIdPropiedad($id);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $propiedad = $this->repository->findById($id);

        if (!$propiedad) {
            throw new NotFoundException('Propiedad no encontrada');
        }

        return $propiedad;
    }

    public function crear(array $rawData, int $usuarioId): Propiedad
    {
        $data = PropiedadSanitizer::sanitizarPropiedad($rawData);
        $data['usuario_id'] = $usuarioId;

        $validacion = PropiedadValidator::validarCrearPropiedad($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if (
            !$this->categoriaRepository->findById(
                (int) $data['categoria_id']
            )
        ) {
            throw new ValidationException([
                'categoria_id' => [
                    'La categoría seleccionada no existe',
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
                    'La localidad seleccionada no existe',
                ],
            ]);
        }

       $propiedad = $this->repository->create($data);

        $this->logActividadService->registrar(
            $usuarioId,
            'Creación de propiedad'
        );

        return $propiedad;
    }

    public function actualizar(int $usuarioId, int $rolId, $propiedadId, array $rawData): void
    {
        if ($rawData === []) {
            throw new BadRequestException(
                'Debe enviar al menos un campo para actualizar'
            );
        }   

        $propiedad = $this->obtener($propiedadId);

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

        $data = PropiedadSanitizer::sanitizarPropiedad($datosRecibidos + [
            'id' => $propiedadId,
            'usuario_id' => $propiedad->usuario_id,
            'categoria_id' => $datosRecibidos['categoria_id'] ?? $propiedad->categoria_id,
            'localidad_id' => $datosRecibidos['localidad_id'] ?? $propiedad->localidad_id,
            'titulo' => $datosRecibidos['titulo'] ?? $propiedad->titulo,
            'descripcion' => $datosRecibidos['descripcion'] ?? $propiedad->descripcion,
            'precio' => $datosRecibidos['precio'] ?? $propiedad->precio,
            'expensas' => $datosRecibidos['expensas'] ?? $propiedad->expensas,
            'direccion' => $datosRecibidos['direccion'] ?? $propiedad->direccion,
            'cantidad_ambientes' => $datosRecibidos['cantidad_ambientes'] ?? $propiedad->cantidad_ambientes,
            'cantidad_dormitorios' => $datosRecibidos['cantidad_dormitorios'] ?? $propiedad->cantidad_dormitorios,
            'cantidad_banos' => $datosRecibidos['cantidad_banos'] ?? $propiedad->cantidad_banos,
            'capacidad' => $datosRecibidos['capacidad'] ?? $propiedad->capacidad,
            'disponible' => $datosRecibidos['disponible'] ?? $propiedad->disponible,
        ]);

        $validacion = PropiedadValidator::validarActualizarPropiedad($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if (
            !$this->categoriaRepository->findById(
                (int) $data['categoria_id']
            )
        ) {
            throw new ValidationException([
                'categoria_id' => [
                    'La categoría seleccionada no existe',
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
                    'La localidad seleccionada no existe',
                ],
            ]);
        }

        $this->repository->update($propiedad, $data);

        $this->logActividadService->registrar(
            $usuarioId,
            'Actualización de propiedad'
        );
    }

    public function eliminar(int $usuarioId, int $rolId, $propiedadId): void
    {
        $propiedad = $this->obtener($propiedadId);

        $this->policy->gestionar(
            $propiedad,
            $usuarioId,
            $rolId
        );

        $this->repository->delete($propiedad);

        $this->logActividadService->registrar(
            $usuarioId,
            'Eliminación de propiedad'
        );
    }

    public function restaurar(int $usuarioId, int $rolId, $propiedadId): void
    {
        $id = PropiedadSanitizer::sanitizarIdPropiedad($propiedadId);

        $validacion = PropiedadValidator::validarSoloIdPropiedad($id);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $propiedad = $this->repository->findDeletedById($id);

        if (!$propiedad) {
            throw new NotFoundException('Propiedad no encontrada');
        }

        $this->policy->gestionar(
            $propiedad,
            $usuarioId,
            $rolId
        );

        if ($propiedad->deleted_at === null) {
            throw new BadRequestException('La propiedad no está eliminada');
        }

        $this->repository->restore($propiedad);

        $this->logActividadService->registrar(
            $usuarioId,
            'Restauración de propiedad'
        );
    }
}
