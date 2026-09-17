<?php

namespace App\Sanitizers;

class ReservaSanitizer
{
    /**
     * Sanitizar datos para crear reserva
     */
    public static function sanitizarCrear(array $data): array
    {
        return [
            'propiedad_id' => self::sanitizarId(
                $data['propiedad_id'] ?? null
            )
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

        $id = filter_var(
            $id,
            FILTER_VALIDATE_INT
        );

        return (
            $id !== false &&
            $id > 0
        )
            ? $id
            : null;
    }

    /**
     * Sanitizar estado
     */
    public static function sanitizarEstado($estado): ?string
    {
        if (
            $estado === null ||
            trim($estado) === ''
        ) {
            return null;
        }

        return strtolower(
            trim($estado)
        );
    }

    /**
     * Sanitizar ID de reserva
     */
    public static function sanitizarReservaId($id): ?int
    {
        return self::sanitizarId($id);
    }

    /**
     * Sanitizar estado para actualización
     */
    public static function sanitizarActualizarEstado(
        array $data
    ): array {
        return [
            'estado' => self::sanitizarEstado(
                $data['estado'] ?? null
            )
        ];
    }
}