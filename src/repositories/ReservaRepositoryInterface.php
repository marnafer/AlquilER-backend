<?php

namespace App\Repositories;

interface ReservaRepositoryInterface
{
    public function getAll(array $filtros = []): array;

    public function findById(int $id);

    public function create(array $data): int;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function restore(int $id): bool;

    public function getByUsuario(int $usuarioId): array;

    public function getByPropiedad(int $propiedadId): array;

}