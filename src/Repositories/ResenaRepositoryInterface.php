<?php

namespace App\Repositories;

use App\Models\Resena;
use Illuminate\Database\Eloquent\Collection;

interface ResenaRepositoryInterface
{
    public function all(array $filtros = []): Collection;

    public function findById(int $id): ?Resena;

    public function findDeletedById(int $id): ?Resena;

    public function create(array $data): Resena;

    public function update(Resena $resena, array $data): bool;

    public function delete(Resena $resena): bool;

    public function restore(Resena $resena): bool;

    public function getByReserva(int $reservaId): Collection;

    public function getByPropiedad(int $propiedadId): Collection;

    public function getByUsuario(int $usuarioId): Collection;

    public function getByCalificador(int $calificadorId): Collection;

    public function getPromedioByPropiedad(int $propiedadId): float;

    public function getPromedioByUsuario(int $usuarioId): float;

    public function existePorReservaYTipo(
        int $reservaId,
        string $tipo
    ): bool;
}