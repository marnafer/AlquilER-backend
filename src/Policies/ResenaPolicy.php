<?php

declare(strict_types=1);

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Resena;
use App\Models\Reserva;
use App\Models\Rol;

class ResenaPolicy
{

    /**
     * Crear una reseña.
     *
     * Tipo propiedad:
     * El inquilino de la reserva califica la propiedad.
     *
     * Tipo inquilino:
     * El propietario de la propiedad califica al inquilino.
     */
    public function crear(
        Reserva $reserva,
        int $usuarioId,
        string $tipo
    ): void {
        if (
            $tipo === 'propiedad' &&
            (int) $reserva->usuario_id === $usuarioId
        ) {
            return;
        }

        if (
            $tipo === 'inquilino' &&
            $reserva->propiedad &&
            (int) $reserva->propiedad->usuario_id === $usuarioId
        ) {
            return;
        }

        throw new ForbiddenException(
            'No tienes permiso para crear esta reseña'
        );
    }

    /**
     * Eliminar una reseña.
     *
     * El autor puede eliminar su propia reseña.
     * El administrador puede eliminar cualquier reseña.
     */
    public function eliminar(
        Resena $resena,
        int $usuarioId,
        int $rolId
    ): void {
        if (
            $rolId === Rol::ADMIN ||
            (int) $resena->calificador_id === $usuarioId
        ) {
            return;
        }

        throw new ForbiddenException(
            'No tienes permiso para eliminar esta reseña'
        );
    }

    /**
     * Restaurar una reseña.
     *
     * Solo el administrador puede restaurarla.
     */
    public function restaurar(int $rolId): void
    {
        if ($rolId === Rol::ADMIN) {
            return;
        }

        throw new ForbiddenException(
            'No tienes permiso para restaurar esta reseña'
        );
    }
}