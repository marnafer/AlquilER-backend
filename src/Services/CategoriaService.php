<?php

declare(strict_types=1);

namespace App\Services;

use App\Cache\FileCache;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Models\Categoria;
use App\Repositories\CategoriaRepositoryInterface;
use App\Sanitizers\CategoriaSanitizer;
use App\Validators\CategoriaValidator;

Class CategoriaService
{
    private const LIST_CACHE_KEY = 'catalog:categories:active:v1';
    private const LIST_CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly CategoriaRepositoryInterface $repository,
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

        $categorias = $this->repository->all($filtros);

        return [
            'items' => $categorias,
            'total' => $categorias->count(),
        ];
    }

    private function formatearListado($categorias): array
    {
        $items = array_map(
            static fn (Categoria $categoria): array => $categoria->toArray(),
            $categorias->all()
        );

        return [
            'items' => $items,
            'total' => count($items),
        ];
    }

    public function obtener($rawId): Categoria
    {
        $id = CategoriaSanitizer::sanitizarIdCategoria($rawId);

        $validacion = CategoriaValidator::validarIdCategoria($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        $categoria = $this->repository->findById($id);

        if (!$categoria) {
            throw new NotFoundException('Categoría no encontrada');
        }

        return $categoria;
    }

    public function crear(array $rawData): Categoria
    {
        $data = CategoriaSanitizer::sanitizarCategoria($rawData);

        $validacion = CategoriaValidator::validarCategoria($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if ($this->repository->existsByName($data['nombre'])) {
            throw new ConflictException(
                    'Categoría existente, no se puede crear otra con el mismo nombre'
            );
        }

        $categoria = $this->repository->create($data);
        $this->invalidarListado();

        return $categoria;
    }

    public function eliminar($rawId): void
    {
        $categoria = $this->obtener($rawId);

        if ($this->repository->hasProperties($categoria)) {
            throw new ConflictException(
                'No se puede eliminar porque tiene propiedades asociadas'
            );
        }

        $this->repository->delete($categoria);
        $this->invalidarListado();
    }

    public function actualizar($rawId, array $rawData): void
    {
        $categoria = $this->obtener($rawId);

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

        $data = CategoriaSanitizer::sanitizarActualizacionCategoria($datosRecibidos);

        $validacion = CategoriaValidator::validarCategoria($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if (
            array_key_exists('nombre', $data)
            && $this->repository->existsByName($data['nombre'], $categoria->id)
        ) {
            throw new ConflictException(
                    'El nombre ya está registrado'
        );
            }

            $this->repository->update($categoria, $data);
            $this->invalidarListado();
        }

    public function restaurar($rawId): void
    {
        $id = CategoriaSanitizer::sanitizarIdCategoria($rawId);

        $validacion = CategoriaValidator::validarIdCategoria($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        $categoria = $this->repository->findDeletedById($id);

        if (!$categoria) {
            throw new NotFoundException('Categoría eliminada no encontrada');
        }

        if (
            $this->repository->existsByName(
                $categoria->nombre,
                $categoria->id
            )
        ) {
            throw new ConflictException(
                'Ya existe una categoría activa con ese nombre'
            );
        }

        $this->repository->restore($categoria);
        $this->invalidarListado();
    }

    private function invalidarListado(): void
    {
        $this->cache?->forget(self::LIST_CACHE_KEY);
    }

}