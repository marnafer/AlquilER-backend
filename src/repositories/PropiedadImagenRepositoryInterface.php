<?php

namespace App\Repositories;

use App\Models\PropiedadImagen;
use Illuminate\Database\Eloquent\Collection;

interface PropiedadImagenRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?PropiedadImagen;

    public function findByPropiedadId(int $propiedadId): Collection;

    public function countByPropiedadId(int $propiedadId): int;

    public function create(array $data): PropiedadImagen;

    public function clearPrincipalByPropiedadId(int $propiedadId): void;

    public function setPrincipal(PropiedadImagen $imagen): void;

    public function delete(PropiedadImagen $imagen): void;

    public function recorrerRutas(callable $callback): void;
}