<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\Rol;

class ReservaPolicy
{

    /**
     * Ver una reserva.
     *
     * Admin: puede ver cualquiera.
     * Usuario: solo si es el inquilino de la reserva
     * o propietario de la propiedad.
     */
    public function puedeVer(
        int $usuarioId,
        int $rolId,
        Reserva $reserva
    ): bool {
        if ($rolId === Rol::ADMIN) {
            return true;
        }

        return $reserva->usuario_id === $usuarioId
            || (
                $reserva->propiedad &&
                (int) $reserva->propiedad->usuario_id === $usuarioId
            );
    }

    /**
     * Crear una reserva.
     *
     * Un usuario no puede reservar una propiedad propia.
     */
    public function puedeCrear(
        int $usuarioId,
        int $propietarioId
    ): bool {
        return $usuarioId !== $propietarioId;
    }

    /**
     * Confirmar una reserva.
     *
     * Solo el propietario de la propiedad o un admin.
     */
    public function puedeConfirmar(
        int $usuarioId,
        int $rolId,
        Reserva $reserva
    ): bool {
        if ($rolId === Rol::ADMIN) {
            return true;
        }

        return $reserva->propiedad &&
            (int) $reserva->propiedad->usuario_id === $usuarioId;
    }

    /**
     * Rechazar una reserva.
     *
     * Solo el propietario de la propiedad o un admin.
     */
    public function puedeRechazar(
        int $usuarioId,
        int $rolId,
        Reserva $reserva
    ): bool {
        if ($rolId === Rol::ADMIN) {
            return true;
        }

        return $reserva->propiedad &&
            (int) $reserva->propiedad->usuario_id === $usuarioId;
    }

    /**
     * Finalizar una reserva.
     *
     * Solo el propietario de la propiedad o un admin.
     */
    public function puedeFinalizar(
        int $usuarioId,
        int $rolId,
        Reserva $reserva
    ): bool {
        if ($rolId === Rol::ADMIN) {
            return true;
        }

        return $reserva->propiedad &&
            (int) $reserva->propiedad->usuario_id === $usuarioId;
    }

    /**
     * Cancelar una reserva.
     *
     * El inquilino, el propietario de la propiedad o un admin.
     */
    public function puedeCancelar(
        int $usuarioId,
        int $rolId,
        Reserva $reserva
    ): bool {
        if ($rolId === Rol::ADMIN) {
            return true;
        }

        if ($reserva->usuario_id === $usuarioId) {
            return true;
        }

        return $reserva->propiedad &&
            (int) $reserva->propiedad->usuario_id === $usuarioId;
    }

    /**
     * Modificar una reserva.
     *
     * Solo admin.
     */
    public function puedeModificar(
        int $rolId
    ): bool {
        return $rolId === Rol::ADMIN;
    }

    /**
     * Eliminar una reserva.
     *
     * Solo admin.
     */
    public function puedeEliminar(
        int $rolId
    ): bool {
        return $rolId === Rol::ADMIN;
    }

    /**
     * Restaurar una reserva.
     *
     * Solo admin.
     */
    public function puedeRestaurar(
        int $rolId
    ): bool {
        return $rolId === Rol::ADMIN;
    }
}