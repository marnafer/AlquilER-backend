<?php

namespace App\Repositories;

interface FavoritoRepositoryInterface
{
    public function getByUserId(int $usuarioId): array;

    public function findByUsuarioAndPropiedad(
        int $usuarioId,
        int $propiedadId
    ): ?\App\Models\Favorito;

    public function exists(int $usuarioId, int $propiedadId): bool;

    public function add(int $usuarioId, int $propiedadId): bool;

    public function remove(int $usuarioId, int $propiedadId): bool;

    public function countByPropiedad(int $propiedadId): int;

    public function getPropiedadIdsByUser(int $usuarioId): array;
}