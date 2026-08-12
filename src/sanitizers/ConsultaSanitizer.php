<?php

namespace App\Sanitizers;

class ConsultaSanitizer
{
    public static function sanitizarConsulta($data): array
    {
        return [
            'id' => self::sanitizarId($data['id'] ?? null),
            'propiedad_id' => self::sanitizarId($data['propiedad_id'] ?? null),
            'usuario_id' => self::sanitizarId($data['usuario_id'] ?? null),
            'mensaje' => self::sanitizarMensaje($data['mensaje'] ?? null),
            'fecha_consulta' => self::sanitizarFecha($data['fecha_consulta'] ?? null)
        ];
    }

    public static function sanitizarId($id)
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var($id, FILTER_VALIDATE_INT);

        return ($id !== false && $id > 0)
            ? $id
            : null;
    }

    public static function sanitizarMensaje($mensaje)
    {
        if ($mensaje === null || $mensaje === '') {
            return null;
        }

        $mensaje = trim($mensaje);
        $mensaje = preg_replace('/\s+/', ' ', $mensaje);
        $mensaje = strip_tags($mensaje);
        $mensaje = htmlspecialchars(
            $mensaje,
            ENT_QUOTES,
            'UTF-8'
        );

        return substr($mensaje, 0, 5000);
    }

    public static function sanitizarFecha($fecha)
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        $timestamp = strtotime($fecha);

        return $timestamp
            ? date('Y-m-d H:i:s', $timestamp)
            : null;
    }
}