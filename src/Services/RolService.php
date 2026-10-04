<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Models\Rol;
use App\Repositories\RolRepositoryInterface;
use App\Sanitizers\RolSanitizer;
use App\Validators\RolValidator;

class RolService
{
    public function __construct(
        private readonly RolRepositoryInterface $repository
    ) {
    }

    public function listar(array $filtros = []): array
    {
        $roles = $this->repository->all($filtros);

        return [
            'items' => $roles,
            'total' => $roles->count(),
        ];
    }

    public function obtener($rawId): Rol
    {
        $id = RolSanitizer::sanitizarIdRol($rawId);

        $validacion = RolValidator::validarIdRol($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        $rol = $this->repository->findById($id);

        if (!$rol) {
            throw new NotFoundException('Rol no encontrado');
        }

        return $rol;
    }

    public function crear(array $rawData): Rol
    {
        $data = RolSanitizer::sanitizarRol($rawData);

        $validacion = RolValidator::validarRol($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if ($this->repository->existsByName($data['nombre'])) {
            throw new ConflictException(
                'Rol existente, no se puede crear otro con el mismo nombre'
            );
        }

        return $this->repository->create($data);
    }

    public function eliminar($rawId): void
    {
        $rol = $this->obtener($rawId);

        if ($this->repository->hasUsers($rol)) {
            throw new ConflictException(
                'No se puede eliminar porque tiene usuarios asociados'
            );
        }

        $this->repository->delete($rol);
    }

    public function actualizar($rawId, array $rawData): void
    {
        $rol = $this->obtener($rawId);

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

        $data = RolSanitizer::sanitizarActualizacionRol(
            $datosRecibidos
        );

        $validacion = RolValidator::validarRol($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if (
            array_key_exists('nombre', $data)
            && $this->repository->existsByName(
                $data['nombre'],
                $rol->id
            )
        ) {
            throw new ConflictException(
                'El nombre ya está registrado'
            );
        }

        $this->repository->update($rol, $data);
    }

    public function restaurar($rawId): void
    {
        $id = RolSanitizer::sanitizarIdRol($rawId);

        $validacion = RolValidator::validarIdRol($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        $rol = $this->repository->findDeletedById($id);

        if (!$rol) {
            throw new NotFoundException(
                'Rol eliminado no encontrado'
            );
        }

        if (
            $this->repository->existsByName(
                $rol->nombre,
                $rol->id
            )
        ) {
            throw new ConflictException(
                'Ya existe un rol activo con ese nombre'
            );
        }

        $this->repository->restore($rol);
    }
}