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

        return mb_substr($mensaje, 0, 5000, 'UTF-8');
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

    public static function sanitizarPerfilInteresado($perfil)
    {
        // Si no se envía el perfil o se envía null, no hay perfil nuevo.
        if ($perfil === null) {
            return null;
        }

        // Conservamos el tipo incorrecto para que el validador lo rechace.
        if (!is_array($perfil)) {
            return $perfil;
        }

        $tieneMascotas = filter_var(
            $perfil['tiene_mascotas'] ?? null,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        $garantias = $perfil['garantias'] ?? [];

            $garantiasSanitizadas = array_values(array_unique(array_map(
                static fn ($garantia) => is_string($garantia)
                    ? trim($garantia)
                    : $garantia,
                $garantias
            )));
        

        return [
            'fecha_mudanza' => self::sanitizarFechaDia(
                $perfil['fecha_mudanza'] ?? null
            ),
            'cantidad_ocupantes' => self::sanitizarEntero(
                $perfil['cantidad_ocupantes'] ?? null
            ),
            'tiene_mascotas' => $tieneMascotas,
            'cantidad_mascotas' => self::sanitizarEntero(
                $perfil['cantidad_mascotas'] ?? null
            ),
            'garantias' => $garantiasSanitizadas,
        ];
    }

    private static function sanitizarFechaDia($fecha): ?string
    {
        if (!is_string($fecha) || $fecha === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        $errores = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($errores !== false
                && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))
            || $date->format('Y-m-d') !== $fecha
        ) {
            return null;
        }

        return $fecha;
    }

    private static function sanitizarEntero($valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $entero = filter_var($valor, FILTER_VALIDATE_INT);

        return $entero === false ? null : $entero;
    }
}