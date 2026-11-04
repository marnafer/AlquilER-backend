<?php

declare(strict_types=1);

namespace App\Services;

use App\Cache\FileCache;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Provincia;
use App\Repositories\ProvinciaRepositoryInterface;
use App\Sanitizers\ProvinciaSanitizer;
use App\Validators\ProvinciaValidator;

class ProvinciaService
{
    private const LIST_CACHE_KEY = 'catalog:provinces:active:v1';
    private const LIST_CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly ProvinciaRepositoryInterface $repository,
        private readonly ?FileCache $cache = null
    ) {
    }

    public function listar(array $filtros = []): array
    {
        if ($this->cache !== null && $filtros === []) {
            return $this->cache->remember(
                self::LIST_CACHE_KEY,
                self::LIST_CACHE_TTL_SECONDS,
                fn (): array => $this->formatearListado(
                    $this->repository->all()
                )
            );
        }

        $provincias = $this->repository->all($filtros);

        return [
            'items' => $provincias,
            'total' => $provincias->count(),
        ];
    }

    private function formatearListado($provincias): array
    {
        $items = array_map(
            static fn (Provincia $provincia): array => $provincia->toArray(),
            $provincias->all()
        );

        return [
            'items' => $items,
            'total' => count($items),
        ];
    }

    public function obtener($rawId): Provincia
    {
        $id = ProvinciaSanitizer::sanitizarId($rawId);

        $validacion = ProvinciaValidator::validarSoloId($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $provincia = $this->repository->findById($id);

        if (!$provincia) {
            throw new NotFoundException(
                'Provincia no encontrada'
            );
        }

        return $provincia;
    }

    public function crear(array $rawData): Provincia
    {
        $data = ProvinciaSanitizer::sanitizarCrear($rawData);

        $validacion = ProvinciaValidator::validar($data);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        if ($this->repository->existsByName($data['nombre'])) {
            throw new ConflictException(
                'Provincia existente, no se puede crear otra con el mismo nombre'
            );
        }

        $provincia = $this->repository->create($data);
        $this->invalidarListado();

        return $provincia;
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
        $this->invalidarListado();
    }

    public function actualizar(
        $rawId,
        array $rawData
    ): void {
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

        $data = ProvinciaSanitizer::sanitizarActualizar(
            $datosRecibidos
        );

        $validacion = ProvinciaValidator::validar($data);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        if (
            array_key_exists('nombre', $data)
            && $this->repository->existsByName(
                $data['nombre'],
                $provincia->id
            )
        ) {
            throw new ConflictException(
                'El nombre ya está registrado'
            );
        }

        $this->repository->update(
            $provincia,
            $data
        );
        $this->invalidarListado();
    }

    public function restaurar($rawId): void
    {
        $id = ProvinciaSanitizer::sanitizarId($rawId);

        $validacion = ProvinciaValidator::validarSoloId($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $provincia = $this->repository->findDeletedById($id);

        if (!$provincia) {
            throw new NotFoundException(
                'Provincia eliminada no encontrada'
            );
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
        $this->invalidarListado();
    }

    private function invalidarListado(): void
    {
        $this->cache?->forget(self::LIST_CACHE_KEY);
    }
}