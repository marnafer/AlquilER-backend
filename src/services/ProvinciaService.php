<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Models\Provincia;
use App\Repositories\ProvinciaRepositoryInterface;
use App\Sanitizers\ProvinciaSanitizer;
use App\Validators\ProvinciaValidator;

Class ProvinciaService 
{
    public function __construct(
        private readonly ProvinciaRepositoryInterface $repository
    ) {
    }

    public function listar(): array
    {
        $provincias = $this->repository->all();

        return [
            'items' => $provincias,
            'total' => $provincias->count(),
        ];
    }

    public function obtener($rawId): Provincia
    {
        $id = ProvinciaSanitizer::sanitizarIdProvincia($rawId);

        $validacion = ProvinciaValidator::validarIdProvincia($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        $provincia = $this->repository->findById($id);

        if (!$provincia) {
            throw new NotFoundException('Provincia no encontrada');
        }

        return $provincia;
    }

    public function crear(array $rawData): Provincia
    {
        $data = ProvinciaSanitizer::sanitizarProvincia($rawData);

        $validacion = ProvinciaValidator::validarProvincia($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if ($this->repository->existsByName($data['nombre'])) {
            throw new ConflictException(
                    'Provincia existente, no se puede crear otra con el mismo nombre'
            );
        }

        return $this->repository->create($data);
    }

    public function eliminar($rawId): void
    {
        $provincia = $this->obtener($rawId);

        if ($this->repository->hasLocalities($provincia)) {
            throw new ConflictException(
                'No se puede eliminar porque tiene localidades asociadas'
            );
        }

        $this->repository->delete($provincia);
    }

    public function actualizar($rawId, array $rawData): void
    {
        $provincia = $this->obtener($rawId);

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
            'nombre'
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

        $data = ProvinciaSanitizer::sanitizarActualizacionProvincia($datosRecibidos);

        $validacion = ProvinciaValidator::validarProvincia($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if (
            array_key_exists('nombre', $data)
            && $this->repository->existsByName($data['nombre'], $provincia->id)
        ) {
            throw new ConflictException(
                    'El nombre ya está registrado'
        );
            }

            $this->repository->update($provincia, $data);
        }

    public function restaurar($rawId): void
    {
        $id = ProvinciaSanitizer::sanitizarIdProvincia($rawId);

        $validacion = ProvinciaValidator::validarIdProvincia($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        $provincia = $this->repository->findDeletedById($id);

        if (!$provincia) {
            throw new NotFoundException('Provincia eliminada no encontrada');
        }

        if (
            $this->repository->existsByName(
                $provincia->nombre,
                $provincia->id
            )
        ) {
            throw new ConflictException(
                'Ya existe una provincia activa con ese nombre'
            );
        }

        $this->repository->restore($provincia);
    }


}