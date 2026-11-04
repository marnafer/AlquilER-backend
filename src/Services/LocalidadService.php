<?php

declare(strict_types=1);

namespace App\Services;

use App\Cache\FileCache;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Localidad;
use App\Repositories\LocalidadRepositoryInterface;
use App\Repositories\ProvinciaRepositoryInterface;
use App\Sanitizers\LocalidadSanitizer;
use App\Validators\LocalidadValidator;

class LocalidadService
{
    private const LIST_CACHE_KEY = 'catalog:localities:active:v1';
    private const LIST_CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly LocalidadRepositoryInterface $localidadRepository,
        private readonly ProvinciaRepositoryInterface $provinciaRepository,
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
                    $this->localidadRepository->all()
                )
            );
        }

        $localidades = $this->localidadRepository->all($filtros);

        return [
            'items' => $localidades,
            'total' => $localidades->count(),
        ];
    }

    private function formatearListado($localidades): array
    {
        $items = array_map(
            static fn (Localidad $localidad): array => $localidad->toArray(),
            $localidades->all()
        );

        return [
            'items' => $items,
            'total' => count($items),
        ];
    }

    public function obtener($rawId): Localidad
    {
        $id = LocalidadSanitizer::sanitizarIdLocalidad($rawId);

        $validacion = LocalidadValidator::validarSoloIdLocalidad($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $localidad = $this->localidadRepository->findById($id);

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

        if (!$this->provinciaRepository->findById($data['provincia_id'])) {
            throw new ValidationException([
                'provincia_id' => ['La provincia no existe'],
            ]);
        }

        if (
            $this->localidadRepository->existsByNameInProvince(
                $data['nombre'],
                $data['provincia_id']
            )
        ) {
            throw new ConflictException(
                'Ya existe una localidad con ese nombre en la provincia seleccionada'
            );
        }

        $localidad = $this->localidadRepository->create($data);
        $this->invalidarListado();

        return $localidad;
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
            array_key_exists('provincia_id', $data)
            && !$this->provinciaRepository->findById($provinciaId)
        ) {
            throw new ValidationException([
                'provincia_id' => ['La provincia no existe'],
            ]);
        }

        if (
            array_key_exists('nombre', $data)
            && $this->localidadRepository->existsByNameInProvince(
                $data['nombre'],
                $provinciaId,
                $localidad->id
            )
        ) {
            throw new ConflictException(
                'Ya existe otra localidad con ese nombre en la provincia'
            );
        }

        $this->localidadRepository->update($localidad, $data);
        $this->invalidarListado();
    }

    public function eliminar($rawId): void
    {
        $localidad = $this->obtener($rawId);

        if ($this->localidadRepository->hasProperties($localidad)) {
            throw new ConflictException(
                'No se puede eliminar porque tiene propiedades asociadas'
            );
        }

        $this->localidadRepository->delete($localidad);
        $this->invalidarListado();
    }

    public function restaurar($rawId): void
    {
        $id = LocalidadSanitizer::sanitizarIdLocalidad($rawId);

        $validacion = LocalidadValidator::validarSoloIdLocalidad($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $localidad = $this->localidadRepository->findDeletedById($id);

        if (!$localidad) {
            throw new NotFoundException('Localidad eliminada no encontrada');
        }

        if (
            $this->localidadRepository->existsByNameInProvince(
                $localidad->nombre,
                $localidad->provincia_id,
                $localidad->id
            )
        ) {
            throw new ConflictException(
                'Ya existe una localidad activa con ese nombre en la provincia'
            );
        }

        $this->localidadRepository->restore($localidad);
        $this->invalidarListado();
    }

    private function invalidarListado(): void
    {
        $this->cache?->forget(self::LIST_CACHE_KEY);
    }
}
