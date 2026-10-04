<?php

namespace App\Middlewares;

use App\Exceptions\ForbiddenException;
use App\Models\Rol;

/**
 * Exige rol admin cuando la petición pide ver la papelera.
 *
 * Los catálogos son de lectura pública, así que sus rutas GET no piden token.
 * Pero eso dejaba que cualquiera pidiera ?solo_eliminados=1 y obtuviera lo
 * borrado: en reseñas, además, con los datos de contacto del calificador.
 *
 * Pedir la papelera no es un filtro de consulta sino una capacidad, así que se
 * exige el permiso solo cuando el flag viene activo. Quien lista sin el flag
 * sigue entrando sin autenticarse.
 */
class PapeleraMiddleware
{
    /**
     * Parámetros que exponen registros soft-deleted.
     */
    private const FLAGS_PAPELERA = [
        'solo_eliminados',
        'incluir_eliminados',
    ];

    /**
     * Indica si la petición actual pide registros eliminados.
     */
    public static function pidePapelera(): bool
    {
        foreach (self::FLAGS_PAPELERA as $flag) {
            if (
                isset($_GET[$flag]) &&
                filter_var($_GET[$flag], FILTER_VALIDATE_BOOLEAN)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Corta con 403 si se pide la papelera sin ser administrador.
     *
     * @return object|null El admin autenticado, o null si no se pidió papelera.
     */
    public static function verificarSiPidePapelera()
    {
        if (!self::pidePapelera()) {
            return null;
        }

        $user = AutenticadorMiddleware::verificar();

        if ((int) $user->rol_id !== Rol::ADMIN) {
            throw new ForbiddenException(
                'Solo un administrador puede ver los registros eliminados'
            );
        }

        return $user;
    }
}