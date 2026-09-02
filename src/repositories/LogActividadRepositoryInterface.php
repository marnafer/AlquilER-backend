<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use App\Models\LogActividad;

interface LogActividadRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?array;

    public function create(array $data): LogActividad;
}
