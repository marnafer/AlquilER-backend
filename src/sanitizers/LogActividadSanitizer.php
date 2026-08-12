<?php

namespace App\Sanitizers;

class LogActividadSanitizer
{
    /**
     * Sanitizar payload completo de log
     */
    public static function sanitizar(array $data): array
    {
        return [
            'id' => self::sanitizarId($data['id'] ?? null),
            'usuario_id' => self::sanitizarUsuarioId($data['usuario_id'] ?? null),
            'accion' => self::sanitizarAccion($data['accion'] ?? null),
            'ip_address' => self::sanitizarIp($data['ip_address'] ?? null),
            'fecha' => self::sanitizarFecha($data['fecha'] ?? null),
        ];
    }

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
     * Sanitizar fecha
     */
    public static function sanitizarFecha($fecha): ?string
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        $timestamp = strtotime($fecha);

        if (!$timestamp) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Obtener IP real del cliente
     */
    public static function getClientIp(): ?string
    {
        $ip = null;

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // puede venir lista de IPs
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipList[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return self::sanitizarIp($ip);
    }

    /**
     * Sanitizar solo campos de creación (sin id)
     */
    public static function sanitizarCrear(array $data): array
    {
        return [
            'usuario_id' => self::sanitizarUsuarioId($data['usuario_id'] ?? null),
            'accion' => self::sanitizarAccion($data['accion'] ?? null),
            'ip_address' => self::sanitizarIp($data['ip_address'] ?? null),
            'fecha' => self::sanitizarFecha($data['fecha'] ?? null),
        ];
    }
}