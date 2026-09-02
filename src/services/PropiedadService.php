<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Propiedad;
use App\Repositories\PropiedadRepositoryInterface;
use App\Sanitizers\PropiedadSanitizer;
use App\Validators\PropiedadValidator;

class PropiedadService
{
    public function __construct(
        private readonly PropiedadRepositoryInterface $repository,
        private readonly LogActividadService $logActividadService
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

        $this->logActividadService->registrar(
            $usuarioId,
            'Creación de propiedad'
        );

        return $this->repository->create($data);
    }

    public function actualizar(int $usuarioId, int $rolId, $propiedadId, array $rawData): void
    {
        $propiedad = $this->obtener($propiedadId);

        if ($rolId !== 2 && (int) $propiedad->usuario_id !== $usuarioId) {
            throw new ForbiddenException(
                'No tienes permiso para modificar esta propiedad'
            );
        }

        if ($rawData === []) {
            throw new BadRequestException(
                'Debe enviar al menos un campo para actualizar'
            );
        }

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

        $this->logActividadService->registrar(
            $usuarioId,
            'Actualización de propiedad'
        );

        $this->repository->update($propiedad, $data);
    }

    public function eliminar(int $usuarioId, int $rolId, $propiedadId): void
    {
        $propiedad = $this->obtener($propiedadId);

        if ($rolId !== 2 && (int) $propiedad->usuario_id !== $usuarioId) {
            throw new ForbiddenException(
                'No tienes permiso para eliminar esta propiedad'
            );
        }

        $this->logActividadService->registrar(
            $usuarioId,
            'Eliminación de propiedad'
        );

        $this->repository->delete($propiedad);
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

        if ($rolId !== 2 && (int) $propiedad->usuario_id !== $usuarioId) {
            throw new ForbiddenException(
                'No tienes permiso para restaurar esta propiedad'
            );
        }

        if ($propiedad->deleted_at === null) {
            throw new BadRequestException('La propiedad no está eliminada');
        }

        $this->logActividadService->registrar(
            $usuarioId,
            'Restauración de propiedad'
        );

        $this->repository->restore($propiedad);
    }
}
