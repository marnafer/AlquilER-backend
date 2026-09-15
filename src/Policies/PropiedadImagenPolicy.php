<?php

declare(strict_types=1);

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Propiedad;
use App\Models\PropiedadImagen;

class PropiedadImagenPolicy
{
    public function gestionarPropiedad(
        Propiedad $propiedad,
        object $user
    ): void {
        if (
            (int) $user->rol_id === 2 ||
            (int) $propiedad->usuario_id === (int) $user->sub
        ) {
            return;
        }

        throw new ForbiddenException(
            'No tiene permisos sobre esta propiedad'
        );
    }

    public function gestionar(
        PropiedadImagen $imagen,
        object $user
    ): void {
        if (
            (int) $user->rol_id === 2 ||
            (int) $imagen->propiedad->usuario_id === (int) $user->sub
        ) {
            return;
        }

        throw new ForbiddenException(
            'No tiene permisos sobre esta imagen'
        );
    }
}