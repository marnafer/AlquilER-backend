<?php

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Propiedad;
use App\Models\PropiedadImagen;
use App\Models\Rol;

class PropiedadImagenPolicy
{
    /**
     * Gestionar imágenes de una propiedad.
     *
     * Admin: puede gestionar cualquiera.
     * Usuario: solo si es propietario de la propiedad.
     */
    public function gestionarPropiedad(
        int $usuarioId,
        int $rolId,
        Propiedad $propiedad
    ): void {
        if ($rolId === Rol::ADMIN) {
            return;
        }

        if ((int) $propiedad->usuario_id === $usuarioId) {
            return;
        }

        throw new ForbiddenException(
            'No tiene permisos sobre esta propiedad'
        );
    }

    /**
     * Gestionar una imagen.
     *
     * Admin: puede gestionar cualquiera.
     * Usuario: solo si es propietario de la propiedad asociada.
     */
    public function gestionar(
        int $usuarioId,
        int $rolId,
        PropiedadImagen $imagen
    ): void {
        if ($rolId === Rol::ADMIN) {
            return;
        }

        if (
            $imagen->propiedad &&
            (int) $imagen->propiedad->usuario_id === $usuarioId
        ) {
            return;
        }

        throw new ForbiddenException(
            'No tiene permisos sobre esta imagen'
        );
    }
}