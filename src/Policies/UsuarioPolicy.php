<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rol;

class UsuarioPolicy
{
    /**
     * Ver datos privados de un usuario.
     *
     * Solo el propio usuario o un administrador.
     */
    public function puedeVer(
        int $usuarioId,
        int $rolId,
        int $usuarioConsultadoId
    ): bool {
        return $rolId === Rol::ADMIN
            || $usuarioId === $usuarioConsultadoId;
    }

    /**
     * Editar los datos de un usuario.
     *
     * Solo el propio usuario o un administrador.
     */
    public function puedeActualizar(
        int $usuarioId,
        int $rolId,
        int $usuarioEditadoId
    ): bool {
        return $rolId === Rol::ADMIN
            || $usuarioId === $usuarioEditadoId;
    }

    /**
     * Eliminar un usuario.
     *
     * Solo el propio usuario o un administrador.
     */
    public function puedeEliminar(
        int $usuarioId,
        int $rolId,
        int $usuarioEliminadoId
    ): bool {
        return $rolId === Rol::ADMIN
            || $usuarioId === $usuarioEliminadoId;
    }

    /**
     * Administrar usuarios: restauración y otras operaciones administrativas.
     *
     * Solo el administrador.
     */
    public function puedeAdministrar(int $rolId): bool
    {
        return $rolId === Rol::ADMIN;
    }

    /**
     * Acceder al perfil público de otro usuario.
     *
     * La relación entre ambos usuarios debe verificarse
     * por separado mediante consultas o reservas.
     */
    public function puedeVerPerfilPublico(
        int $usuarioId,
        int $rolId,
        int $usuarioConsultadoId,
        bool $existeRelacion
    ): bool {
        if ($usuarioId === $usuarioConsultadoId) {
            return false;
        }

        return $rolId === Rol::ADMIN || $existeRelacion;
    }
}
