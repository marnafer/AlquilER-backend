<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Consulta;
use App\Models\Propiedad;
use App\Models\Rol;

class ConsultaPolicy
{

    /**
     * Chat: Solo interesado o dueño de la propiedad.
     */
    public function puedeParticipar(int $usuarioId, Consulta $consulta): bool
    {
        $esInteresado = $consulta->usuario_id === $usuarioId;
        
        $esDueno = false;
        // Usamos relationLoaded para evitar que Eloquent intente consultar la BD si no fue seteada explícitamente
        if ($consulta->relationLoaded('propiedad') && $consulta->propiedad) {
            $esDueno = $consulta->propiedad->usuario_id === $usuarioId;
        }

        return $esInteresado || $esDueno;
    }

    /**
     * Actualizar: Solo admin o el creador de la consulta (interesado).
     */
    public function puedeActualizar(int $usuarioId, int $rolId, Consulta $consulta): bool
    {
        return $rolId === Rol::ADMIN || $consulta->usuario_id === $usuarioId;
    }

    /**
     * Eliminar/Restaurar: Solo el administrador.
     */
    public function puedeAdministrar(int $rolId): bool
    {
        return $rolId === Rol::ADMIN;
    }

    /**
     * Ver listado de un usuario: Solo el propio usuario o un admin.
     */
    public function puedeVerDeUsuario(int $usuarioLogueadoId, int $rolId, int $usuarioConsultadoId): bool
    {
        return $rolId === Rol::ADMIN || $usuarioLogueadoId === $usuarioConsultadoId;
    }

    /**
     * Ver listado de una propiedad: Solo el dueño de la propiedad o un admin.
     */
    public function puedeVerDePropiedad(int $usuarioId, int $rolId, Propiedad $propiedad): bool
    {
        return $rolId === Rol::ADMIN || $propiedad->usuario_id === $usuarioId;
    }
}