<?php

namespace App\Sanitizers;

class ReservaSanitizer
{
    /**
     * Sanitizar reserva completa
     */
    public static function sanitizar(array $data): array
    {
        return [
            'id' => self::sanitizarId($data['id'] ?? null),
            'propiedad_id' => self::sanitizarId($data['propiedad_id'] ?? null),
            'usuario_id' => self::sanitizarId($data['usuario_id'] ?? null),
            'fecha_inicio_alquiler' => self::sanitizarFecha($data['fecha_inicio_alquiler'] ?? null),
            'fecha_fin_alquiler' => self::sanitizarFecha($data['fecha_fin_alquiler'] ?? null),
            'estado' => self::sanitizarEstado($data['estado'] ?? null),
            'fecha_reserva' => self::sanitizarFechaHora($data['fecha_reserva'] ?? null)
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

        return ($id !== false && $id > 0)
            ? $id
            : null;
    }

    /**
     * Sanitizar fecha (Y-m-d)
     */
    public static function sanitizarFecha($fecha): ?string
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        $timestamp = strtotime($fecha);

        return $timestamp
            ? date('Y-m-d', $timestamp)
            : null;
    }

    /**
     * Sanitizar fecha y hora
     */
    public static function sanitizarFechaHora($fecha): ?string
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        $timestamp = strtotime($fecha);

        return $timestamp
            ? date('Y-m-d H:i:s', $timestamp)
            : null;
    }

    /**
     * Sanitizar estado
     */
    public static function sanitizarEstado($estado): ?string
    {
        if ($estado === null || $estado === '') {
            return null;
        }

        return strtolower(
            trim($estado)
        );
    }

    /**
     * Sanitizar solo estado
     */
    public static function sanitizarSoloEstado($estado): ?string
    {
        return self::sanitizarEstado($estado);
    }

    /**
     * Sanitizar solo IDs
     */
    public static function sanitizarIds(array $data): array
    {
        return [
            'id' => self::sanitizarId($data['id'] ?? null),
            'propiedad_id' => self::sanitizarId($data['propiedad_id'] ?? null),
            'usuario_id' => self::sanitizarId($data['usuario_id'] ?? null)
        ];
    }
}