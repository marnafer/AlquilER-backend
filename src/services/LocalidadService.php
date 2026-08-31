<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Localidad;
use App\Repositories\LocalidadRepositoryInterface;
use App\Sanitizers\LocalidadSanitizer;
use App\Validators\LocalidadValidator;

class LocalidadService
{
    public function __construct(
        private readonly LocalidadRepositoryInterface $repository
    ) {
    }

    public function listar(): array
    {
        $localidades = $this->repository->all();

        return [
            'items' => $localidades,
            'total' => $localidades->count(),
        ];
    }

    public function obtener($rawId): Localidad
    {
        $id = LocalidadSanitizer::sanitizarIdLocalidad($rawId);

        $validacion = LocalidadValidator::validarIdLocalidad($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']],
            ]);
        }

        $localidad = $this->repository->findById($id);

        if (!$localidad) {
            throw new NotFoundException('Localidad no encontrada');
        }

        return $localidad;
    }

    public function crear(array $rawData): Localidad
    {
        $data = LocalidadSanitizer::sanitizarLocalidad($rawData);

        $validacion = LocalidadValidator::validarLocalidad($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if (
            $this->repository->existsByNameInProvince(
                $data['nombre'],
                $data['provincia_id']
            )
        ) {
            throw new ConflictException(
                'Ya existe una localidad con ese nombre en la provincia seleccionada'
            );
        }

        return $this->repository->create($data);
    }

    public function actualizar($rawId, array $rawData): void
    {
        $localidad = $this->obtener($rawId);

        if ($rawData === []) {
            throw new BadRequestException(
                'Debe enviar al menos un campo para actualizar'
            );
        }

        unset(
            $rawData['id'],
            $rawData['deleted_at']
        );

        $camposPermitidos = [
            'nombre',
            'codigo_postal',
            'provincia_id',
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

        $data = LocalidadSanitizer::sanitizarActualizacionLocalidad($datosRecibidos);

        $validacion = LocalidadValidator::validarLocalidad($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $provinciaId = $data['provincia_id'] ?? $localidad->provincia_id;

        if (
            array_key_exists('nombre', $data)
            && $this->repository->existsByNameInProvince(
                $data['nombre'],
                $provinciaId,
                $localidad->id
            )
        ) {
            throw new ConflictException(
                'Ya existe otra localidad con ese nombre en la provincia'
            );
        }

        $this->repository->update($localidad, $data);
    }

    public function eliminar($rawId): void
    {
        $localidad = $this->obtener($rawId);

        if ($this->repository->hasProperties($localidad)) {
            throw new ConflictException(
                'No se puede eliminar porque tiene propiedades asociadas'
            );
        }

        $this->repository->delete($localidad);
    }

    public function restaurar($rawId): void
    {
        $id = LocalidadSanitizer::sanitizarIdLocalidad($rawId);

        $validacion = LocalidadValidator::validarIdLocalidad($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']],
            ]);
        }

        $localidad = $this->repository->findDeletedById($id);

        if (!$localidad) {
            throw new NotFoundException('Localidad eliminada no encontrada');
        }

        if (
            $this->repository->existsByNameInProvince(
                $localidad->nombre,
                $localidad->provincia_id,
                $localidad->id
            )
        ) {
            throw new ConflictException(
                'Ya existe una localidad activa con ese nombre en la provincia'
            );
        }

        $this->repository->restore($localidad);
    }
}
