<?php

namespace App\Repositories;

use App\Models\Propiedad;
use Illuminate\Database\Eloquent\Collection;

interface PropiedadRepositoryInterface
{
    public function all(): Collection;

    public function allParaAdmin(array $filtros = []): Collection;

    public function porUsuario(int $usuarioId): Collection;

    public function findById(int $id): ?Propiedad;

    public function findDeletedById(int $id): ?Propiedad;

    public function create(array $data): Propiedad;

    public function update(Propiedad $propiedad, array $data): bool;

    public function delete(Propiedad $propiedad): bool;

    public function restore(Propiedad $propiedad): bool;

    public function findByIdForUpdate(int $id): ?Propiedad;
}
