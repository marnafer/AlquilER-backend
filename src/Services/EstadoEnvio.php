<?php

declare(strict_types=1);

namespace App\Services;

enum EstadoEnvio: string
{
    case Enviado = 'enviado';
    case GuardadoLocalmente = 'guardado_localmente';
    case GuardadoTrasFallo = 'guardado_tras_fallo';
    case Fallido = 'fallido';

    public function fueEnviado(): bool
    {
        return $this === self::Enviado;
    }

    public function fueGuardadoLocalmente(): bool
    {
        return $this === self::GuardadoLocalmente
            || $this === self::GuardadoTrasFallo;
    }

    public function esExitoso(): bool
    {
        return $this === self::Enviado
            || $this === self::GuardadoLocalmente;
    }
}
