<?php

namespace App\Repositories;

use App\Models\MensajeConsulta;
use Illuminate\Database\Eloquent\Collection;

interface MensajeConsultaRepositoryInterface
{
    public function create(array $data): MensajeConsulta;

    public function findByConsultaId(int $consultaId): Collection;

    public function findLatestByConsultaId(int $consultaId, int $limit): Collection;

    public function findOlderByConsultaId(
        int $consultaId,
        int $beforeId,
        int $limit
    ): Collection;

    public function findNewerByConsultaId(
        int $consultaId,
        int $afterId,
        int $limit
    ): Collection;

    public function findById(int $id): ?MensajeConsulta;

    public function update(int $id, array $data): bool;

    public function delete(int $id): void;
}