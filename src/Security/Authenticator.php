<?php

declare(strict_types=1);

namespace App\Security;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Helpers\TokenProviderInterface;
use App\Http\Request;
use App\Models\Rol;

/**
 * Decide si una petición está autenticada y con qué rol.
 *
 * Reemplaza a los antiguos middlewares estáticos (AutenticadorMiddleware y
 * PapeleraMiddleware), que leían $_SERVER y guardaban el proveedor de tokens
 * en un atributo estático global. Aquí el proveedor viene por el constructor,
 * así que un test puede inyectar un stub sin tocar el estado del proceso.
 */
class Authenticator
{
    /**
     * Parámetros de query que exponen registros soft-deleted.
     */
    private const FLAGS_PAPELERA = [
        'solo_eliminados',
        'incluir_eliminados',
    ];

    public function __construct(
        private readonly TokenProviderInterface $tokenProvider
    ) {
    }

    /**
     * Devuelve el usuario autenticado o lanza UnauthorizedException.
     *
     * @return object
     */
    public function verificar(Request $request): object
    {
        $authHeader = $request->header('Authorization');

        // 1. Verificar que exista
        if (!$authHeader) {
            throw new UnauthorizedException('Token requerido');
        }

        // 2. Verificar formato Bearer
        $authHeader = trim($authHeader);

        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new UnauthorizedException('Formato de token inválido');
        }

        // 3. Extraer token
        $token = trim(substr($authHeader, 7));

        // 4. Validar token
        $user = $this->tokenProvider->validate($token);

        if (!$user) {
            throw new UnauthorizedException('Token inválido o expirado');
        }

        return $user;
    }

    /**
     * Verifica que el usuario autenticado sea administrador.
     *
     * @return object
     */
    public function soloAdmin(Request $request): object
    {
        $user = $this->verificar($request);

        if ((int) $user->rol_id !== Rol::ADMIN) {
            throw new ForbiddenException('Solo administradores');
        }

        return $user;
    }

    /**
     * Corta con 403 si se pide la papelera sin ser administrador.
     *
     * Los catálogos son de lectura pública, así que sus rutas GET no piden
     * token. Pero eso dejaba que cualquiera pidiera ?solo_eliminados=1 y
     * obtuviera lo borrado: en reseñas, además, con los datos de contacto del
     * calificador. Pedir la papelera no es un filtro de consulta sino una
     * capacidad, así que se exige el permiso solo cuando el flag viene activo.
     *
     * @return object|null El admin autenticado, o null si no se pidió papelera.
     */
    public function verificarSiPidePapelera(Request $request): ?object
    {
        if (!$this->pidePapelera($request)) {
            return null;
        }

        $user = $this->verificar($request);

        if ((int) $user->rol_id !== Rol::ADMIN) {
            throw new ForbiddenException(
                'Solo un administrador puede ver los registros eliminados'
            );
        }

        return $user;
    }

    /**
     * Indica si la petición pide registros eliminados.
     */
    public function pidePapelera(Request $request): bool
    {
        foreach (self::FLAGS_PAPELERA as $flag) {
            $valor = $request->query($flag);

            if ($valor !== null && filter_var($valor, FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        }

        return false;
    }
}
