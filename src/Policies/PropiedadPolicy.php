<?php

declare(strict_types=1);

namespace App\Policies;

use App\Exceptions\ForbiddenException;
use App\Models\Propiedad;

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
            $rolId === 2 ||
            (int) $propiedad->usuario_id === $usuarioId
        ) {
            return;
        }

        throw new ForbiddenException(
            'No tienes permiso para gestionar esta propiedad'
        );
    }
}