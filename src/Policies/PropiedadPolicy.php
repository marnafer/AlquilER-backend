<?php

declare(strict_types=1);

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Propiedad;
use App\Models\Rol;

class PropiedadPolicy
{
    /**
     * Verifica si el usuario puede gestionar la propiedad.
     *
     * El administrador puede gestionar cualquier propiedad.
     * El propietario solo puede gestionar sus propias propiedades.
     */
    public function gestionar(
        Propiedad $propiedad,
        int $usuarioId,
        int $rolId
    ): void {
        if (
            $rolId === Rol::ADMIN ||
            (int) $propiedad->usuario_id === $usuarioId
        ) {
            return;
        }

        throw new ForbiddenException(
            'No tienes permiso para gestionar esta propiedad'
        );
    }
}