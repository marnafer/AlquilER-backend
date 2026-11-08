<?php

declare(strict_types=1);

namespace App\Services;

use App\Cache\FileCache;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Servicio;
use App\Repositories\ServicioRepositoryInterface;
use App\Sanitizers\ServicioSanitizer;
use App\Validators\ServicioValidator;

class ServicioService
{
    private const LIST_CACHE_KEY = 'catalog:services:active:v1';
    private const LIST_CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly ServicioRepositoryInterface $repository,
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

        $servicios = $this->repository->all($filtros);

        return [
            'items' => $servicios,
            'total' => $servicios->count(),
        ];
    }

    private function formatearListado($servicios): array
    {
        $items = array_map(
            static fn (Servicio $servicio): array => $servicio->toArray(),
            $servicios->all()
        );

        return [
            'items' => $items,
            'total' => count($items),
        ];
    }

    public function obtener($rawId): Servicio
    {
        $id = ServicioSanitizer::sanitizarId(
            $rawId
        );

        $error = ServicioValidator::validarId(
            $id
        );

        if ($error) {
            throw new ValidationException([
                'id' => [$error]
            ]);
        }

        $servicio = $this->repository->findById($id);

        if (!$servicio) {
            throw new NotFoundException(
                'Servicio no encontrado'
            );
        }

        return $servicio;
    }

    public function crear(array $rawData): Servicio
    {
        $data = ServicioSanitizer::sanitizar(
            $rawData
        );

        $validacion = ServicioValidator::validar(
            $data
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        if (
            $this->repository->existsByName(
                $data['nombre']
            )
        ) {
            throw new ConflictException(
                'Servicio existente, no se puede crear otro con el mismo nombre'
            );
        }

        $servicio = $this->repository->create($data);
        $this->invalidarListado();

        return $servicio;
    }

    public function actualizar(
        $rawId,
        array $rawData
    ): void {
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

        $data = ServicioSanitizer::sanitizarActualizacion(
            $datosRecibidos
        );

        $validacion = ServicioValidator::validar(
            $data
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        if (
            $this->repository->existsByName(
                $data['nombre'],
                $servicio->id
            )
        ) {
            throw new ConflictException(
                'Ya existe un servicio con ese nombre'
            );
        }

        $this->repository->update(
            $servicio,
            $data
        );
        $this->invalidarListado();
    }

    public function eliminar($rawId): void
    {
        $id = ServicioSanitizer::sanitizarId(
            $rawId
        );

        $error = ServicioValidator::validarId(
            $id
        );

        if ($error) {
            throw new ValidationException([
                'id' => [$error]
            ]);
        }

        $servicio = $this->repository->findById($id);

        if (!$servicio) {
            throw new NotFoundException(
                'Servicio no encontrado'
            );
        }

        if (
            $this->repository->hasProperties($servicio)
        ) {
            throw new ConflictException(
                'No se puede eliminar el servicio porque tiene propiedades asociadas'
            );
        }

        $this->repository->delete($servicio);
        $this->invalidarListado();
    }

    public function restaurar($rawId): void
    {
        $id = ServicioSanitizer::sanitizarId(
            $rawId
        );

        $error = ServicioValidator::validarId(
            $id
        );

        if ($error) {
            throw new ValidationException([
                'id' => [$error]
            ]);
        }

        $servicio = $this->repository->findDeletedById($id);

        if (!$servicio) {
            throw new NotFoundException(
                'Servicio no encontrado o no eliminado'
            );
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
        $this->invalidarListado();
    }

    private function invalidarListado(): void
    {
        $this->cache?->forget(self::LIST_CACHE_KEY);
    }
}