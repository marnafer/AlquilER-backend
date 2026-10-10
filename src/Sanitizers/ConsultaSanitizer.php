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
            'fecha_consulta' => self::sanitizarFecha($data['fecha_consulta'] ?? null),
            'perfil_interesado' => self::sanitizarPerfilInteresado(
                $data['perfil_interesado'] ?? null
            ),
        ];
    }

    public static function sanitizarPerfilInteresado($perfil): ?array
    {
        if (!is_array($perfil)) {
            return null;
        }

        $garantiasPermitidas = [
            'recibo_sueldo',
            'garantia_propietaria',
            'seguro_caucion',
            'garante',
        ];
        $mascotas = filter_var(
            $perfil['tiene_mascotas'] ?? null,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        return [
            'fecha_mudanza' => self::sanitizarFechaDia(
                $perfil['fecha_mudanza'] ?? null
            ),
            'cantidad_ocupantes' => self::sanitizarEntero(
                $perfil['cantidad_ocupantes'] ?? null
            ),
            'tiene_mascotas' => $mascotas,
            'cantidad_mascotas' => self::sanitizarEntero(
                $perfil['cantidad_mascotas'] ?? null
            ),
            'garantias' => array_values(array_unique(array_filter(
                is_array($perfil['garantias'] ?? null)
                    ? $perfil['garantias']
                    : [],
                fn($garantia) => in_array($garantia, $garantiasPermitidas, true)
            ))),
        ];
    }

    private static function sanitizarFechaDia($fecha): ?string
    {
        if (!is_string($fecha)) {
            return null;
        }

        $fecha = trim($fecha);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        return $date && $date->format('Y-m-d') === $fecha
            ? $fecha
            : null;
    }

    private static function sanitizarEntero($valor): ?int
    {
        $entero = filter_var($valor, FILTER_VALIDATE_INT);
        return $entero === false ? null : $entero;
    }

    public static function sanitizarId($id)
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = filter_var($id, FILTER_VALIDATE_INT);

        return ($id !== false && $id > 0)
            ? (int) $id 
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