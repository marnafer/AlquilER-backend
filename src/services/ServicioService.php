<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Models\Servicio;
use App\Repositories\ServicioRepositoryInterface;
use App\Sanitizers\ServicioSanitizer;
use App\Validators\ServicioValidator;

class ServicioService
{
    public function __construct(
        private readonly ServicioRepositoryInterface $repository
    ) {
    }

    public function listar(): array
    {
        $servicios = $this->repository->all();

        return [
            'items' => $servicios,
            'total' => $servicios->count(),
        ];
    }

    public function obtener($rawId): Servicio
    {
        $id = ServicioSanitizer::sanitizarIdServicio($rawId);

        $validacion = ServicioValidator::validarIdServicio($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        $servicio = $this->repository->findById($id);

        if (!$servicio) {
            throw new NotFoundException('Servicio no encontrado');
        }

        return $servicio;
    }

    public function crear(array $rawData): Servicio
    {
        $data = ServicioSanitizer::sanitizarServicio($rawData);

        $validacion = ServicioValidator::validarServicio($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if ($this->repository->existsByName($data['nombre'])) {
            throw new ConflictException(
                    'Servicio existente, no se puede crear otro con el mismo nombre'
            );
        }

        return $this->repository->create($data);
    }

    public function actualizar($rawId, array $rawData): Servicio
    {
         $servicio = $this->obtener($rawId);

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

        $data = ServicioSanitizer::sanitizarActualizacionServicio($datosRecibidos);

        $validacionData = ServicioValidator::validarServicio($data);

        if (!$validacionData['success']) {
            throw new ValidationException($validacionData['errors']);
        }

        if ($this->repository->existsByName($data['nombre'], $servicio->id)) {
            throw new ConflictException('Ya existe un servicio con ese nombre');
        }

        $this->repository->update($servicio, $data);

        return $servicio;
    }

    public function eliminar($rawId): void
    {
        $id = ServicioSanitizer::sanitizarIdServicio($rawId);

        $validacionId = ServicioValidator::validarIdServicio($id);

        if (!$validacionId['success']) {
            throw new ValidationException([
                'id' => [$validacionId['error']]
            ]);
        }

        $servicio = $this->repository->findById($id);

        if (!$servicio) {
            throw new NotFoundException('Servicio no encontrado');
        }

        if ($this->repository->hasProperties($servicio)) {
            throw new ConflictException(
                'No se puede eliminar el servicio porque tiene propiedades asociadas'
            );
        }

        $this->repository->delete($servicio);
    }

    public function restaurar($rawId): void
    {
        $id = ServicioSanitizer::sanitizarIdServicio($rawId);

        $validacionId = ServicioValidator::validarIdServicio($id);

        if (!$validacionId['success']) {
            throw new ValidationException([
                'id' => [$validacionId['error']]
            ]);
        }

        $servicio = $this->repository->findDeletedById($id);

        if (!$servicio) {
            throw new NotFoundException('Servicio no encontrado o no eliminado');
        }

        if (
            $this->repository->existsByName(
                $servicio->nombre,
                $servicio->id
            )
        ) {
            throw new ConflictException(
                'Ya existe un servicio activo con ese nombre'
            );
        }

        $this->repository->restore($servicio);
    }

}    
    