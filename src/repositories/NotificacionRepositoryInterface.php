<?php

namespace App\Repositories;

use App\Models\Notificacion;

interface NotificacionRepositoryInterface
{
    public function getByUsuario(int $usuarioId): array;

    public function contarNoLeidas(int $usuarioId): int;

    public function findById(int $id): ?Notificacion;

    public function create(array $data): Notificacion;

    public function update(int $id, array $data): bool;

    public function marcarTodasLeidas(int $usuarioId): bool;
}