<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Favorito;
use App\Models\Rol;

class FavoritoPolicy
{

    /**
     * Ver los favoritos de un usuario.
     *
     * Un usuario puede consultar sus propios favoritos.
     * Un administrador puede consultar los favoritos de cualquier usuario.
     */
    public function puedeVerDeUsuario(
        int $usuarioLogueadoId,
        int $rolId,
        int $usuarioConsultadoId
    ): bool {
        return $rolId === Rol::ADMIN
            || $usuarioLogueadoId === $usuarioConsultadoId;
    }

    /**
     * Eliminar un favorito.
     *
     * Solo el usuario propietario del favorito puede eliminarlo.
     */
    public function puedeEliminar(
        int $usuarioId,
        Favorito $favorito
    ): bool {
        return $favorito->usuario_id === $usuarioId;
    }
}