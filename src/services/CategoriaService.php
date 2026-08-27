<?php

declare(strict_types=1);

namespace App\Services;

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
    public function __construct(
        private readonly CategoriaRepositoryInterface $repository
    ) {
    }

    public function listar(): array
    {
        $categorias = $this->repository->all();

        return [
            'items' => $categorias,
            'total' => $categorias->count(),
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

        return $this->repository->create($data);
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
    }


}