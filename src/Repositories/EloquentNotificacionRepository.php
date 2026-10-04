<?php

namespace App\Repositories;

use App\Models\Notificacion;

class EloquentNotificacionRepository implements NotificacionRepositoryInterface
{
    public function getByUsuario(int $usuarioId): array
    {
        return Notificacion::query()
            ->where('usuario_id', $usuarioId)
            ->orderBy('fecha_notificacion', 'desc')
            ->get()
            ->toArray();
    }

    public function contarNoLeidas(int $usuarioId): int
    {
        return Notificacion::query()
            ->where('usuario_id', $usuarioId)
            ->where('leida', 0)
            ->count();
    }

    public function findById(int $id): ?Notificacion
    {
        return Notificacion::withTrashed()->find($id);
    }

    public function create(array $data): Notificacion
    {
        return Notificacion::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $notificacion = Notificacion::find($id);

        if (!$notificacion) {
            return false;
        }

        return $notificacion->update($data);
    }

    public function marcarTodasLeidas(int $usuarioId): bool
    {
        return Notificacion::query()
            ->where('usuario_id', $usuarioId)
            ->where('leida', 0)
            ->update(['leida' => 1]) > 0;
    }
}