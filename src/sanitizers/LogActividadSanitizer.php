<?php

namespace App\Sanitizers;

class LogActividadSanitizer
{

    /**
     * Sanitizar ID
     */
    public static function sanitizarId($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var($id, FILTER_VALIDATE_INT);
        return ($id !== false && $id > 0) ? $id : null;
    }

    /**
     * Sanitizar usuario_id
     */
    public static function sanitizarUsuarioId($id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var($id, FILTER_VALIDATE_INT);
        return ($id !== false && $id > 0) ? $id : null;
    }

    /**
     * Sanitizar acción (texto libre controlado)
     */
    public static function sanitizarAccion($accion): ?string
    {
        if ($accion === null || $accion === '') {
            return null;
        }

        $accion = trim($accion);
        $accion = preg_replace('/\s+/u', ' ', $accion);
        $accion = strip_tags($accion);

        $accion = htmlspecialchars(
            $accion,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        if (mb_strlen($accion) > 255) {
            $accion = mb_substr($accion, 0, 255);
        }

        return $accion;
    }

    /**
     * Sanitizar IP
     */
    public static function sanitizarIp($ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        $ip = trim($ip);

        // Solo aceptar IPs válidas
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        return $ip;
    }

    /**
     * Sanitizar solo campos de creación (sin id)
     */
    public static function sanitizarCrear(array $data): array
    {
        return [
            'usuario_id' => self::sanitizarUsuarioId($data['usuario_id'] ?? null),
            'accion' => self::sanitizarAccion($data['accion'] ?? null),
            'ip_address' => self::sanitizarIp($data['ip_address'] ?? null)
        ];
    }
}