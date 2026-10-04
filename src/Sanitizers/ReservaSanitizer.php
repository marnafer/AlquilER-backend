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
            ),
            'fecha_inicio_alquiler' => self::sanitizarFecha(
                $data['fecha_inicio_alquiler'] ?? null
            ),
            'fecha_fin_alquiler' => self::sanitizarFecha(
                $data['fecha_fin_alquiler'] ?? null
            )
        ];
    }

    /**
     * Sanitizar fecha en formato Y-m-d
     */
    public static function sanitizarFecha($fecha): ?string
    {
        if ($fecha === null || trim($fecha) === '') {
            return null;
        }

        $fecha = trim($fecha);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
            return null;
        }

        return $fecha;
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